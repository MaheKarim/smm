<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Models\GoogleAnalyticsProperty;
use App\Models\GoogleAnalyticsData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncGoogleAnalyticsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected SocialAccount $socialAccount,
        protected SyncLog $syncLog
    ) {}

    public function handle(): void
    {
        $this->syncLog->markAsRunning();
        $recordsSynced = 0;

        try {
            $accessToken = $this->socialAccount->access_token;
            $property = $this->socialAccount->googleAnalyticsProperty;

            if (!$property) {
                throw new \Exception('Google Analytics Property not found');
            }

            // Refresh token if needed
            if ($this->socialAccount->isTokenExpired()) {
                $this->refreshAccessToken();
                $accessToken = $this->socialAccount->fresh()->access_token;
            }

            // Sync overview metrics
            $overviewResponse = Http::withToken($accessToken)
                ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$property->property_id}:runReport", [
                    'dateRanges' => [
                        ['startDate' => Carbon::now()->subDays(30)->format('Y-m-d'), 'endDate' => Carbon::now()->format('Y-m-d')],
                    ],
                    'dimensions' => [
                        ['name' => 'date'],
                    ],
                    'metrics' => [
                        ['name' => 'sessions'],
                        ['name' => 'totalUsers'],
                        ['name' => 'newUsers'],
                        ['name' => 'activeUsers'],
                        ['name' => 'screenPageViews'],
                        ['name' => 'averageSessionDuration'],
                        ['name' => 'bounceRate'],
                        ['name' => 'engagementRate'],
                        ['name' => 'engagedSessions'],
                        ['name' => 'eventCount'],
                        ['name' => 'conversions'],
                    ],
                ]);

            if ($overviewResponse->successful()) {
                $rows = $overviewResponse->json()['rows'] ?? [];
                $recordsSynced += $this->processOverviewData($property, $rows);
            }

            // Sync traffic sources
            $sourcesResponse = Http::withToken($accessToken)
                ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$property->property_id}:runReport", [
                    'dateRanges' => [
                        ['startDate' => Carbon::now()->subDays(30)->format('Y-m-d'), 'endDate' => Carbon::now()->format('Y-m-d')],
                    ],
                    'dimensions' => [
                        ['name' => 'date'],
                        ['name' => 'sessionSource'],
                    ],
                    'metrics' => [
                        ['name' => 'sessions'],
                        ['name' => 'totalUsers'],
                    ],
                ]);

            if ($sourcesResponse->successful()) {
                $rows = $sourcesResponse->json()['rows'] ?? [];
                $recordsSynced += $this->processDimensionData($property, $rows, 'source');
            }

            // Sync top pages
            $pagesResponse = Http::withToken($accessToken)
                ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$property->property_id}:runReport", [
                    'dateRanges' => [
                        ['startDate' => Carbon::now()->subDays(30)->format('Y-m-d'), 'endDate' => Carbon::now()->format('Y-m-d')],
                    ],
                    'dimensions' => [
                        ['name' => 'date'],
                        ['name' => 'pagePath'],
                    ],
                    'metrics' => [
                        ['name' => 'screenPageViews'],
                        ['name' => 'averageSessionDuration'],
                    ],
                ]);

            if ($pagesResponse->successful()) {
                $rows = $pagesResponse->json()['rows'] ?? [];
                $recordsSynced += $this->processDimensionData($property, $rows, 'page');
            }

            $this->socialAccount->update(['last_sync_at' => now()]);
            $this->syncLog->markAsCompleted($recordsSynced);

        } catch (\Exception $e) {
            Log::error('Google Analytics Sync Error: ' . $e->getMessage());
            $this->syncLog->markAsFailed($e->getMessage());
        }
    }

    protected function refreshAccessToken(): void
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_analytics.client_id'),
            'client_secret' => config('services.google_analytics.client_secret'),
            'refresh_token' => $this->socialAccount->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->socialAccount->update([
                'access_token' => $data['access_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in']),
            ]);
        }
    }

    protected function processOverviewData(GoogleAnalyticsProperty $property, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $date = $row['dimensionValues'][0]['value'] ?? null;
            if (!$date) continue;

            // Convert YYYYMMDD to Y-m-d
            $formattedDate = Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');
            $metrics = $row['metricValues'] ?? [];

            GoogleAnalyticsData::updateOrCreate(
                [
                    'google_analytics_property_id' => $property->id,
                    'date' => $formattedDate,
                    'dimension_type' => null,
                    'dimension_value' => null,
                ],
                [
                    'sessions' => (int) ($metrics[0]['value'] ?? 0),
                    'total_users' => (int) ($metrics[1]['value'] ?? 0),
                    'new_users' => (int) ($metrics[2]['value'] ?? 0),
                    'active_users' => (int) ($metrics[3]['value'] ?? 0),
                    'pageviews' => (int) ($metrics[4]['value'] ?? 0),
                    'average_session_duration' => (float) ($metrics[5]['value'] ?? 0),
                    'bounce_rate' => (float) ($metrics[6]['value'] ?? 0),
                    'engagement_rate' => (float) ($metrics[7]['value'] ?? 0),
                    'engaged_sessions' => (int) ($metrics[8]['value'] ?? 0),
                    'events_count' => (int) ($metrics[9]['value'] ?? 0),
                    'conversions' => (int) ($metrics[10]['value'] ?? 0),
                ]
            );
            $count++;
        }

        return $count;
    }

    protected function processDimensionData(GoogleAnalyticsProperty $property, array $rows, string $dimensionType): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $dimensions = $row['dimensionValues'] ?? [];
            $metrics = $row['metricValues'] ?? [];

            $date = $dimensions[0]['value'] ?? null;
            $dimensionValue = $dimensions[1]['value'] ?? null;

            if (!$date || !$dimensionValue) continue;

            $formattedDate = Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');

            $data = [
                'google_analytics_property_id' => $property->id,
                'date' => $formattedDate,
                'dimension_type' => $dimensionType,
                'dimension_value' => $dimensionValue,
            ];

            if ($dimensionType === 'source') {
                $data['sessions'] = (int) ($metrics[0]['value'] ?? 0);
                $data['total_users'] = (int) ($metrics[1]['value'] ?? 0);
            } elseif ($dimensionType === 'page') {
                $data['pageviews'] = (int) ($metrics[0]['value'] ?? 0);
                $data['average_session_duration'] = (float) ($metrics[1]['value'] ?? 0);
            }

            GoogleAnalyticsData::updateOrCreate(
                [
                    'google_analytics_property_id' => $property->id,
                    'date' => $formattedDate,
                    'dimension_type' => $dimensionType,
                    'dimension_value' => $dimensionValue,
                ],
                $data
            );
            $count++;
        }

        return $count;
    }
}

