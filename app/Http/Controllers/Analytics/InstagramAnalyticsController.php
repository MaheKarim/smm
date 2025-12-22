<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\InstagramAccount;
use App\Models\InstagramPost;
use App\Models\InstagramAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InstagramAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        
        // Get all Instagram accounts
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'instagram')
            ->where('status', 'active')
            ->with('instagramAccount')
            ->get();
        
        // Get selected account or first one
        $selectedAccountId = $request->input('account');
        $selectedAccount = $selectedAccountId 
            ? $accounts->firstWhere('id', $selectedAccountId)
            : $accounts->first();
        
        $accountData = null;
        $analyticsData = [];
        $posts = collect();
        $chartData = ['labels' => [], 'reach' => [], 'impressions' => [], 'engagement' => []];
        
        if ($selectedAccount && $selectedAccount->instagramAccount) {
            $igAccount = $selectedAccount->instagramAccount;
            
            // Get account level analytics
            $analytics = InstagramAnalytics::where('instagram_account_id', $igAccount->id)
                ->where('metric_type', 'account')
                ->where('date', '>=', $startDate)
                ->orderBy('date', 'asc')
                ->get();
            
            // Prepare chart data
            foreach ($analytics as $analytic) {
                $chartData['labels'][] = $analytic->date->format('M d');
                $chartData['reach'][] = $analytic->reach;
                $chartData['impressions'][] = $analytic->impressions;
                $chartData['engagement'][] = $analytic->likes + $analytic->comments + $analytic->saves + $analytic->shares;
            }
            
            // Get totals
            $analyticsData = [
                'total_reach' => $analytics->sum('reach'),
                'total_impressions' => $analytics->sum('impressions'),
                'total_profile_views' => $analytics->sum('profile_views'),
                'total_website_clicks' => $analytics->sum('website_clicks'),
                'total_likes' => $analytics->sum('likes'),
                'total_comments' => $analytics->sum('comments'),
                'total_saves' => $analytics->sum('saves'),
                'total_shares' => $analytics->sum('shares'),
                'followers_gained' => $analytics->sum('followers_gained'),
                'avg_engagement_rate' => $analytics->avg('engagement_rate') * 100,
            ];
            
            // Get posts
            $posts = InstagramPost::where('instagram_account_id', $igAccount->id)
                ->with(['analytics' => function($q) use ($startDate) {
                    $q->where('date', '>=', $startDate);
                }])
                ->where('is_story', false)
                ->orderBy('published_at', 'desc')
                ->limit(12)
                ->get();
            
            $accountData = $igAccount;
        }
        
        return view('analytics.instagram', compact(
            'accounts',
            'selectedAccount',
            'accountData',
            'analyticsData',
            'posts',
            'chartData',
            'days'
        ));
    }
}

