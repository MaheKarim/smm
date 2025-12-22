<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\FacebookAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FacebookAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        
        // Get all Facebook accounts
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('facebookPage')
            ->get();
        
        // Get selected account or first one
        $selectedAccountId = $request->input('account');
        $selectedAccount = $selectedAccountId 
            ? $accounts->firstWhere('id', $selectedAccountId)
            : $accounts->first();
        
        $pageData = null;
        $analyticsData = [];
        $posts = collect();
        $chartData = ['labels' => [], 'reach' => [], 'engagement' => [], 'impressions' => []];
        
        if ($selectedAccount && $selectedAccount->facebookPage) {
            $page = $selectedAccount->facebookPage;
            
            // Get page level analytics
            $analytics = FacebookAnalytics::where('facebook_page_id', $page->id)
                ->where('metric_type', 'page')
                ->where('date', '>=', $startDate)
                ->orderBy('date', 'asc')
                ->get();
            
            // Prepare chart data
            foreach ($analytics as $analytic) {
                $chartData['labels'][] = $analytic->date->format('M d');
                $chartData['reach'][] = $analytic->reach;
                $chartData['engagement'][] = $analytic->engaged_users;
                $chartData['impressions'][] = $analytic->impressions;
            }
            
            // Get totals
            $analyticsData = [
                'total_reach' => $analytics->sum('reach'),
                'total_impressions' => $analytics->sum('impressions'),
                'total_engagement' => $analytics->sum('engaged_users'),
                'total_reactions' => $analytics->sum('reactions_total'),
                'total_comments' => $analytics->sum('comments'),
                'total_shares' => $analytics->sum('shares'),
                'avg_engagement_rate' => $analytics->avg('engagement_rate') * 100,
            ];
            
            // Get top posts
            $posts = FacebookPost::where('facebook_page_id', $page->id)
                ->with(['analytics' => function($q) use ($startDate) {
                    $q->where('date', '>=', $startDate)->where('metric_type', 'post');
                }])
                ->orderBy('published_at', 'desc')
                ->limit(10)
                ->get();
            
            $pageData = $page;
        }
        
        return view('analytics.facebook', compact(
            'accounts',
            'selectedAccount',
            'pageData',
            'analyticsData',
            'posts',
            'chartData',
            'days'
        ));
    }
}

