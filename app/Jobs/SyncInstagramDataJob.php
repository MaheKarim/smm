<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Models\InstagramAccount;
use App\Models\InstagramPost;
use App\Models\InstagramAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncInstagramDataJob implements ShouldQueue
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
            $account = $this->socialAccount->instagramAccount;

            if (!$account) {
                throw new \Exception('Instagram Account not found');
            }

            // Update account stats
            $accountResponse = Http::get("https://graph.facebook.com/v18.0/{$account->instagram_id}", [
                'access_token' => $accessToken,
                'fields' => 'followers_count,follows_count,media_count,profile_picture_url,biography,website',
            ]);

            if ($accountResponse->successful()) {
                $data = $accountResponse->json();
                $account->update([
                    'followers_count' => $data['followers_count'] ?? $account->followers_count,
                    'follows_count' => $data['follows_count'] ?? $account->follows_count,
                    'media_count' => $data['media_count'] ?? $account->media_count,
                    'profile_picture_url' => $data['profile_picture_url'] ?? $account->profile_picture_url,
                    'biography' => $data['biography'] ?? $account->biography,
                    'website' => $data['website'] ?? $account->website,
                ]);
            }

            // Sync account insights
            $insightsResponse = Http::get("https://graph.facebook.com/v18.0/{$account->instagram_id}/insights", [
                'access_token' => $accessToken,
                'metric' => 'impressions,reach,profile_views,website_clicks,follower_count',
                'period' => 'day',
                'since' => Carbon::now()->subDays(30)->timestamp,
                'until' => Carbon::now()->timestamp,
            ]);

            if ($insightsResponse->successful()) {
                $insightsData = $insightsResponse->json()['data'] ?? [];
                $recordsSynced += $this->processAccountInsights($account, $insightsData);
            }

            // Sync media (posts)
            $mediaResponse = Http::get("https://graph.facebook.com/v18.0/{$account->instagram_id}/media", [
                'access_token' => $accessToken,
                'fields' => 'id,media_type,media_url,thumbnail_url,permalink,caption,timestamp,like_count,comments_count',
                'limit' => 50,
            ]);

            if ($mediaResponse->successful()) {
                $mediaData = $mediaResponse->json()['data'] ?? [];
                $recordsSynced += $this->processMedia($account, $mediaData, $accessToken);
            }

            $this->socialAccount->update(['last_sync_at' => now()]);
            $this->syncLog->markAsCompleted($recordsSynced);

        } catch (\Exception $e) {
            Log::error('Instagram Sync Error: ' . $e->getMessage());
            $this->syncLog->markAsFailed($e->getMessage());
        }
    }

    protected function processAccountInsights(InstagramAccount $account, array $insightsData): int
    {
        $count = 0;
        $metricsMap = [
            'impressions' => 'impressions',
            'reach' => 'reach',
            'profile_views' => 'profile_views',
            'website_clicks' => 'website_clicks',
            'follower_count' => 'followers_gained',
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
            InstagramAnalytics::updateOrCreate(
                [
                    'instagram_account_id' => $account->id,
                    'instagram_post_id' => null,
                    'date' => $date,
                    'metric_type' => 'account',
                ],
                $metrics
            );
            $count++;
        }

        return $count;
    }

    protected function processMedia(InstagramAccount $account, array $mediaData, string $accessToken): int
    {
        $count = 0;

        foreach ($mediaData as $media) {
            $post = InstagramPost::updateOrCreate(
                [
                    'instagram_account_id' => $account->id,
                    'media_id' => $media['id'],
                ],
                [
                    'media_type' => $media['media_type'],
                    'media_url' => $media['media_url'] ?? null,
                    'thumbnail_url' => $media['thumbnail_url'] ?? null,
                    'permalink' => $media['permalink'] ?? null,
                    'caption' => $media['caption'] ?? null,
                    'hashtags' => $this->extractHashtags($media['caption'] ?? ''),
                    'published_at' => isset($media['timestamp']) ? Carbon::parse($media['timestamp']) : null,
                ]
            );

            // Get media insights
            $insightsResponse = Http::get("https://graph.facebook.com/v18.0/{$media['id']}/insights", [
                'access_token' => $accessToken,
                'metric' => 'impressions,reach,saved,engagement',
            ]);

            if ($insightsResponse->successful()) {
                $this->processMediaInsights($account, $post, $insightsResponse->json()['data'] ?? [], $media);
            }

            $count++;
        }

        return $count;
    }

    protected function processMediaInsights(InstagramAccount $account, InstagramPost $post, array $insightsData, array $mediaData): void
    {
        $metrics = [
            'likes' => $mediaData['like_count'] ?? 0,
            'comments' => $mediaData['comments_count'] ?? 0,
        ];

        foreach ($insightsData as $metric) {
            switch ($metric['name']) {
                case 'impressions':
                    $metrics['impressions'] = $metric['values'][0]['value'] ?? 0;
                    break;
                case 'reach':
                    $metrics['reach'] = $metric['values'][0]['value'] ?? 0;
                    break;
                case 'saved':
                    $metrics['saves'] = $metric['values'][0]['value'] ?? 0;
                    break;
            }
        }

        $metricType = match ($post->media_type) {
            'REELS' => 'reel',
            'STORY' => 'story',
            default => 'post',
        };

        InstagramAnalytics::updateOrCreate(
            [
                'instagram_account_id' => $account->id,
                'instagram_post_id' => $post->id,
                'date' => $post->published_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'metric_type' => $metricType,
            ],
            $metrics
        );
    }

    protected function extractHashtags(string $caption): array
    {
        preg_match_all('/#(\w+)/', $caption, $matches);
        return $matches[1] ?? [];
    }
}

