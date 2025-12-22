<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\YouTubeChannel;
use App\Models\YouTubeVideo;
use App\Models\YouTubeAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class YouTubeAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        
        // Get all YouTube accounts
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'youtube')
            ->where('status', 'active')
            ->with('youtubeChannel')
            ->get();
        
        // Get selected account or first one
        $selectedAccountId = $request->input('account');
        $selectedAccount = $selectedAccountId 
            ? $accounts->firstWhere('id', $selectedAccountId)
            : $accounts->first();
        
        $channelData = null;
        $analyticsData = [];
        $videos = collect();
        $chartData = ['labels' => [], 'views' => [], 'watchTime' => [], 'subscribers' => []];
        
        if ($selectedAccount && $selectedAccount->youtubeChannel) {
            $channel = $selectedAccount->youtubeChannel;
            
            // Get channel level analytics
            $analytics = YouTubeAnalytics::where('youtube_channel_id', $channel->id)
                ->where('metric_type', 'channel')
                ->where('date', '>=', $startDate)
                ->orderBy('date', 'asc')
                ->get();
            
            // Prepare chart data
            foreach ($analytics as $analytic) {
                $chartData['labels'][] = $analytic->date->format('M d');
                $chartData['views'][] = $analytic->views;
                $chartData['watchTime'][] = round($analytic->watch_time_minutes / 60, 1);
                $chartData['subscribers'][] = $analytic->subscribers_gained - $analytic->subscribers_lost;
            }
            
            // Get totals
            $analyticsData = [
                'total_views' => $analytics->sum('views'),
                'total_watch_time' => round($analytics->sum('watch_time_minutes') / 60, 1),
                'avg_view_duration' => $analytics->avg('average_view_duration'),
                'total_subscribers_gained' => $analytics->sum('subscribers_gained'),
                'total_subscribers_lost' => $analytics->sum('subscribers_lost'),
                'total_likes' => $analytics->sum('likes'),
                'total_comments' => $analytics->sum('comments'),
                'avg_ctr' => $analytics->avg('impressions_ctr') * 100,
                'estimated_revenue' => $analytics->sum('estimated_revenue'),
            ];
            
            // Get top videos
            $videos = YouTubeVideo::where('youtube_channel_id', $channel->id)
                ->with(['analytics' => function($q) use ($startDate) {
                    $q->where('date', '>=', $startDate)->where('metric_type', 'video');
                }])
                ->orderBy('published_at', 'desc')
                ->limit(10)
                ->get();
            
            $channelData = $channel;
        }
        
        return view('analytics.youtube', compact(
            'accounts',
            'selectedAccount',
            'channelData',
            'analyticsData',
            'videos',
            'chartData',
            'days'
        ));
    }
}

