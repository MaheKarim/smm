<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Models\YouTubeChannel;
use App\Models\YouTubeVideo;
use App\Models\YouTubeAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncYouTubeDataJob implements ShouldQueue
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
            $channel = $this->socialAccount->youtubeChannel;

            if (!$channel) {
                throw new \Exception('YouTube Channel not found');
            }

            // Refresh token if needed
            if ($this->socialAccount->isTokenExpired()) {
                $this->refreshAccessToken();
                $accessToken = $this->socialAccount->fresh()->access_token;
            }

            // Update channel stats
            $channelResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'statistics',
                    'id' => $channel->channel_id,
                ]);

            if ($channelResponse->successful()) {
                $stats = $channelResponse->json()['items'][0]['statistics'] ?? [];
                $channel->update([
                    'subscriber_count' => $stats['subscriberCount'] ?? $channel->subscriber_count,
                    'video_count' => $stats['videoCount'] ?? $channel->video_count,
                    'view_count' => $stats['viewCount'] ?? $channel->view_count,
                ]);
            }

            // Sync channel analytics
            $analyticsResponse = Http::withToken($accessToken)
                ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                    'ids' => 'channel==' . $channel->channel_id,
                    'startDate' => Carbon::now()->subDays(30)->format('Y-m-d'),
                    'endDate' => Carbon::now()->format('Y-m-d'),
                    'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost,likes,comments,shares',
                    'dimensions' => 'day',
                ]);

            if ($analyticsResponse->successful()) {
                $rows = $analyticsResponse->json()['rows'] ?? [];
                $recordsSynced += $this->processAnalytics($channel, $rows);
            }

            // Sync videos
            $uploadsPlaylistId = 'UU' . substr($channel->channel_id, 2);
            $videosResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/playlistItems', [
                    'part' => 'snippet,contentDetails',
                    'playlistId' => $uploadsPlaylistId,
                    'maxResults' => 50,
                ]);

            if ($videosResponse->successful()) {
                $videos = $videosResponse->json()['items'] ?? [];
                $recordsSynced += $this->processVideos($channel, $videos, $accessToken);
            }

            $this->socialAccount->update(['last_sync_at' => now()]);
            $this->syncLog->markAsCompleted($recordsSynced);

        } catch (\Exception $e) {
            Log::error('YouTube Sync Error: ' . $e->getMessage());
            $this->syncLog->markAsFailed($e->getMessage());
        }
    }

    protected function refreshAccessToken(): void
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
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

    protected function processAnalytics(YouTubeChannel $channel, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            YouTubeAnalytics::updateOrCreate(
                [
                    'youtube_channel_id' => $channel->id,
                    'youtube_video_id' => null,
                    'date' => $row[0],
                    'metric_type' => 'channel',
                ],
                [
                    'views' => $row[1] ?? 0,
                    'watch_time_minutes' => $row[2] ?? 0,
                    'average_view_duration' => $row[3] ?? 0,
                    'subscribers_gained' => $row[4] ?? 0,
                    'subscribers_lost' => $row[5] ?? 0,
                    'likes' => $row[6] ?? 0,
                    'comments' => $row[7] ?? 0,
                    'shares' => $row[8] ?? 0,
                ]
            );
            $count++;
        }

        return $count;
    }

    protected function processVideos(YouTubeChannel $channel, array $videos, string $accessToken): int
    {
        $count = 0;

        foreach ($videos as $item) {
            $videoId = $item['contentDetails']['videoId'] ?? null;
            if (!$videoId) continue;

            // Get video details
            $videoResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,contentDetails,statistics',
                    'id' => $videoId,
                ]);

            if (!$videoResponse->successful()) continue;

            $videoData = $videoResponse->json()['items'][0] ?? null;
            if (!$videoData) continue;

            $snippet = $videoData['snippet'];
            $contentDetails = $videoData['contentDetails'];
            $statistics = $videoData['statistics'];

            // Parse duration
            $duration = $contentDetails['duration'];
            $durationSeconds = $this->parseDuration($duration);

            YouTubeVideo::updateOrCreate(
                [
                    'youtube_channel_id' => $channel->id,
                    'video_id' => $videoId,
                ],
                [
                    'title' => $snippet['title'],
                    'description' => $snippet['description'] ?? null,
                    'thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? null,
                    'duration' => $duration,
                    'duration_seconds' => $durationSeconds,
                    'category_id' => $snippet['categoryId'] ?? null,
                    'tags' => $snippet['tags'] ?? [],
                    'privacy_status' => $contentDetails['privacyStatus'] ?? 'public',
                    'is_short' => $durationSeconds <= 60,
                    'published_at' => Carbon::parse($snippet['publishedAt']),
                ]
            );

            $count++;
        }

        return $count;
    }

    protected function parseDuration(string $duration): int
    {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        
        $hours = isset($matches[1]) ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
        $seconds = isset($matches[3]) ? (int) $matches[3] : 0;
        
        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}

