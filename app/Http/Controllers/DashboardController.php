<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Models\FacebookAnalytics;
use App\Models\YouTubeAnalytics;
use App\Models\InstagramAnalytics;
use App\Models\GoogleAnalyticsData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get connected accounts summary
        $connectedAccounts = $this->getConnectedAccountsSummary($user);
        
        // Get platform stats
        $platformStats = $this->getPlatformStats($user);
        
        // Get aggregated metrics for charts
        $chartData = $this->getChartData($user);
        
        // Get top performing posts
        $topPosts = $this->getTopPosts($user);
        
        // Get recent sync logs
        $recentSyncs = SyncLog::where('user_id', $user->id)
            ->with('socialAccount')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'connectedAccounts',
            'platformStats',
            'chartData',
            'topPosts',
            'recentSyncs'
        ));
    }

    protected function getConnectedAccountsSummary($user): array
    {
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->groupBy('platform');

        return [
            'facebook' => [
                'count' => $accounts->get('facebook', collect())->count(),
                'accounts' => $accounts->get('facebook', collect()),
            ],
            'youtube' => [
                'count' => $accounts->get('youtube', collect())->count(),
                'accounts' => $accounts->get('youtube', collect()),
            ],
            'instagram' => [
                'count' => $accounts->get('instagram', collect())->count(),
                'accounts' => $accounts->get('instagram', collect()),
            ],
            'google_analytics' => [
                'count' => $accounts->get('google_analytics', collect())->count(),
                'accounts' => $accounts->get('google_analytics', collect()),
            ],
        ];
    }

    protected function getPlatformStats($user): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sixtyDaysAgo = Carbon::now()->subDays(60);

        // Get Facebook stats
        $facebookStats = $this->getFacebookStats($user, $thirtyDaysAgo, $sixtyDaysAgo);
        
        // Get YouTube stats
        $youtubeStats = $this->getYouTubeStats($user, $thirtyDaysAgo, $sixtyDaysAgo);
        
        // Get Instagram stats
        $instagramStats = $this->getInstagramStats($user, $thirtyDaysAgo, $sixtyDaysAgo);
        
        // Get Google Analytics stats
        $gaStats = $this->getGoogleAnalyticsStats($user, $thirtyDaysAgo, $sixtyDaysAgo);

        return [
            'facebook' => $facebookStats,
            'youtube' => $youtubeStats,
            'instagram' => $instagramStats,
            'google_analytics' => $gaStats,
        ];
    }

    protected function getFacebookStats($user, $thirtyDaysAgo, $sixtyDaysAgo): array
    {
        $accountIds = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'facebook')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return ['views' => 0, 'change' => 0, 'engagement' => 0];
        }

        // Current period
        $currentViews = FacebookAnalytics::whereHas('facebookPage', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->sum('impressions');

        // Previous period
        $previousViews = FacebookAnalytics::whereHas('facebookPage', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->whereBetween('date', [$sixtyDaysAgo, $thirtyDaysAgo])
        ->sum('impressions');

        $change = $previousViews > 0 
            ? round((($currentViews - $previousViews) / $previousViews) * 100, 1) 
            : 0;

        $engagement = FacebookAnalytics::whereHas('facebookPage', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->selectRaw('SUM(reactions_total + comments + shares) as total')
        ->value('total') ?? 0;

        return [
            'views' => $currentViews,
            'change' => $change,
            'engagement' => $engagement,
        ];
    }

    protected function getYouTubeStats($user, $thirtyDaysAgo, $sixtyDaysAgo): array
    {
        $accountIds = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'youtube')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return ['views' => 0, 'change' => 0, 'watchTime' => 0];
        }

        $currentViews = YouTubeAnalytics::whereHas('youtubeChannel', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->sum('views');

        $previousViews = YouTubeAnalytics::whereHas('youtubeChannel', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->whereBetween('date', [$sixtyDaysAgo, $thirtyDaysAgo])
        ->sum('views');

        $change = $previousViews > 0 
            ? round((($currentViews - $previousViews) / $previousViews) * 100, 1) 
            : 0;

        $watchTime = YouTubeAnalytics::whereHas('youtubeChannel', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->sum('watch_time_minutes');

        return [
            'views' => $currentViews,
            'change' => $change,
            'watchTime' => $watchTime,
        ];
    }

    protected function getInstagramStats($user, $thirtyDaysAgo, $sixtyDaysAgo): array
    {
        $accountIds = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'instagram')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return ['reach' => 0, 'change' => 0, 'engagement' => 0];
        }

        $currentReach = InstagramAnalytics::whereHas('instagramAccount', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->sum('reach');

        $previousReach = InstagramAnalytics::whereHas('instagramAccount', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->whereBetween('date', [$sixtyDaysAgo, $thirtyDaysAgo])
        ->sum('reach');

        $change = $previousReach > 0 
            ? round((($currentReach - $previousReach) / $previousReach) * 100, 1) 
            : 0;

        $engagement = InstagramAnalytics::whereHas('instagramAccount', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->selectRaw('SUM(likes + comments + saves + shares) as total')
        ->value('total') ?? 0;

        return [
            'reach' => $currentReach,
            'change' => $change,
            'engagement' => $engagement,
        ];
    }

    protected function getGoogleAnalyticsStats($user, $thirtyDaysAgo, $sixtyDaysAgo): array
    {
        $accountIds = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'google_analytics')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return ['sessions' => 0, 'change' => 0, 'users' => 0];
        }

        $currentSessions = GoogleAnalyticsData::whereHas('property', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->whereNull('dimension_type')
        ->sum('sessions');

        $previousSessions = GoogleAnalyticsData::whereHas('property', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->whereBetween('date', [$sixtyDaysAgo, $thirtyDaysAgo])
        ->whereNull('dimension_type')
        ->sum('sessions');

        $change = $previousSessions > 0 
            ? round((($currentSessions - $previousSessions) / $previousSessions) * 100, 1) 
            : 0;

        $users = GoogleAnalyticsData::whereHas('property', function($q) use ($accountIds) {
            $q->whereIn('social_account_id', $accountIds);
        })
        ->where('date', '>=', $thirtyDaysAgo)
        ->whereNull('dimension_type')
        ->sum('total_users');

        return [
            'sessions' => $currentSessions,
            'change' => $change,
            'users' => $users,
        ];
    }

    protected function getChartData($user): array
    {
        $days = 30;
        $labels = [];
        $facebookData = [];
        $youtubeData = [];
        $instagramData = [];
        $gaData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            
            // In a real implementation, this would fetch actual data
            // For now, we'll return zeros or demo data
            $facebookData[] = 0;
            $youtubeData[] = 0;
            $instagramData[] = 0;
            $gaData[] = 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'facebook' => $facebookData,
                'youtube' => $youtubeData,
                'instagram' => $instagramData,
                'google_analytics' => $gaData,
            ],
        ];
    }

    protected function getTopPosts($user): array
    {
        // Return empty array for now - will be populated when accounts are connected
        return [];
    }
}

