<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\FacebookAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncFacebookDataJob implements ShouldQueue
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
            $page = $this->socialAccount->facebookPage;

            if (!$page) {
                throw new \Exception('Facebook Page not found');
            }

            // Sync page insights
            $insightsResponse = Http::get("https://graph.facebook.com/v18.0/{$page->page_id}/insights", [
                'access_token' => $accessToken,
                'metric' => 'page_impressions,page_reach,page_engaged_users,page_post_engagements',
                'period' => 'day',
                'since' => Carbon::now()->subDays(30)->timestamp,
                'until' => Carbon::now()->timestamp,
            ]);

            if ($insightsResponse->successful()) {
                $insightsData = $insightsResponse->json()['data'] ?? [];
                $recordsSynced += $this->processPageInsights($page, $insightsData);
            }

            // Sync posts
            $postsResponse = Http::get("https://graph.facebook.com/v18.0/{$page->page_id}/posts", [
                'access_token' => $accessToken,
                'fields' => 'id,message,story,created_time,permalink_url,full_picture,shares,type',
                'limit' => 50,
            ]);

            if ($postsResponse->successful()) {
                $postsData = $postsResponse->json()['data'] ?? [];
                $recordsSynced += $this->processPosts($page, $postsData, $accessToken);
            }

            // Update page stats
            $pageResponse = Http::get("https://graph.facebook.com/v18.0/{$page->page_id}", [
                'access_token' => $accessToken,
                'fields' => 'followers_count,fan_count',
            ]);

            if ($pageResponse->successful()) {
                $pageData = $pageResponse->json();
                $page->update([
                    'followers_count' => $pageData['followers_count'] ?? $page->followers_count,
                    'likes_count' => $pageData['fan_count'] ?? $page->likes_count,
                ]);
            }

            $this->socialAccount->update(['last_sync_at' => now()]);
            $this->syncLog->markAsCompleted($recordsSynced);

        } catch (\Exception $e) {
            Log::error('Facebook Sync Error: ' . $e->getMessage());
            $this->syncLog->markAsFailed($e->getMessage());
        }
    }

    protected function processPageInsights(FacebookPage $page, array $insightsData): int
    {
        $count = 0;
        $metricsMap = [
            'page_impressions' => 'impressions',
            'page_reach' => 'reach',
            'page_engaged_users' => 'engaged_users',
            'page_post_engagements' => 'reactions_total',
        ];

        $dataByDate = [];

        foreach ($insightsData as $metric) {
            $metricName = $metric['name'];
            $mappedName = $metricsMap[$metricName] ?? null;

            if (!$mappedName) continue;

            foreach ($metric['values'] ?? [] as $value) {
                $date = Carbon::parse($value['end_time'])->format('Y-m-d');
                if (!isset($dataByDate[$date])) {
                    $dataByDate[$date] = [];
                }
                $dataByDate[$date][$mappedName] = $value['value'] ?? 0;
            }
        }

        foreach ($dataByDate as $date => $metrics) {
            FacebookAnalytics::updateOrCreate(
                [
                    'facebook_page_id' => $page->id,
                    'facebook_post_id' => null,
                    'date' => $date,
                    'metric_type' => 'page',
                ],
                $metrics
            );
            $count++;
        }

        return $count;
    }

    protected function processPosts(FacebookPage $page, array $postsData, string $accessToken): int
    {
        $count = 0;

        foreach ($postsData as $postData) {
            $post = FacebookPost::updateOrCreate(
                [
                    'facebook_page_id' => $page->id,
                    'post_id' => $postData['id'],
                ],
                [
                    'message' => $postData['message'] ?? null,
                    'story' => $postData['story'] ?? null,
                    'post_type' => $postData['type'] ?? 'status',
                    'permalink_url' => $postData['permalink_url'] ?? null,
                    'full_picture' => $postData['full_picture'] ?? null,
                    'published_at' => isset($postData['created_time']) ? Carbon::parse($postData['created_time']) : null,
                ]
            );

            // Fetch post insights
            $postInsightsResponse = Http::get("https://graph.facebook.com/v18.0/{$postData['id']}/insights", [
                'access_token' => $accessToken,
                'metric' => 'post_impressions,post_reach,post_reactions_by_type_total,post_clicks,post_engaged_users',
            ]);

            if ($postInsightsResponse->successful()) {
                $this->processPostInsights($page, $post, $postInsightsResponse->json()['data'] ?? []);
            }

            $count++;
        }

        return $count;
    }

    protected function processPostInsights(FacebookPage $page, FacebookPost $post, array $insightsData): void
    {
        $metrics = [];

        foreach ($insightsData as $metric) {
            $value = $metric['values'][0]['value'] ?? 0;
            switch ($metric['name']) {
                case 'post_impressions':
                    $metrics['impressions'] = $value;
                    break;
                case 'post_reach':
                    $metrics['reach'] = $value;
                    break;
                case 'post_clicks':
                    $metrics['clicks'] = $value;
                    break;
                case 'post_engaged_users':
                    $metrics['engaged_users'] = $value;
                    break;
                case 'post_reactions_by_type_total':
                    if (is_array($value)) {
                        $metrics['reactions_like'] = $value['like'] ?? 0;
                        $metrics['reactions_love'] = $value['love'] ?? 0;
                        $metrics['reactions_haha'] = $value['haha'] ?? 0;
                        $metrics['reactions_wow'] = $value['wow'] ?? 0;
                        $metrics['reactions_sad'] = $value['sad'] ?? 0;
                        $metrics['reactions_angry'] = $value['angry'] ?? 0;
                        $metrics['reactions_total'] = array_sum($value);
                    }
                    break;
            }
        }

        if (!empty($metrics)) {
            FacebookAnalytics::updateOrCreate(
                [
                    'facebook_page_id' => $page->id,
                    'facebook_post_id' => $post->id,
                    'date' => $post->published_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                    'metric_type' => 'post',
                ],
                $metrics
            );
        }
    }
}

