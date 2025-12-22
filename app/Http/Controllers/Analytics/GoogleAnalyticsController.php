<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\GoogleAnalyticsProperty;
use App\Models\GoogleAnalyticsData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GoogleAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        
        // Get all Google Analytics accounts
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('platform', 'google_analytics')
            ->where('status', 'active')
            ->with('googleAnalyticsProperty')
            ->get();
        
        // Get selected account or first one
        $selectedAccountId = $request->input('account');
        $selectedAccount = $selectedAccountId 
            ? $accounts->firstWhere('id', $selectedAccountId)
            : $accounts->first();
        
        $propertyData = null;
        $analyticsData = [];
        $chartData = ['labels' => [], 'sessions' => [], 'users' => [], 'pageviews' => []];
        $trafficSources = [];
        $topPages = [];
        
        if ($selectedAccount && $selectedAccount->googleAnalyticsProperty) {
            $property = $selectedAccount->googleAnalyticsProperty;
            
            // Get overview analytics
            $analytics = GoogleAnalyticsData::where('google_analytics_property_id', $property->id)
                ->whereNull('dimension_type')
                ->where('date', '>=', $startDate)
                ->orderBy('date', 'asc')
                ->get();
            
            // Prepare chart data
            foreach ($analytics as $analytic) {
                $chartData['labels'][] = $analytic->date->format('M d');
                $chartData['sessions'][] = $analytic->sessions;
                $chartData['users'][] = $analytic->total_users;
                $chartData['pageviews'][] = $analytic->pageviews;
            }
            
            // Get totals
            $analyticsData = [
                'total_sessions' => $analytics->sum('sessions'),
                'total_users' => $analytics->sum('total_users'),
                'new_users' => $analytics->sum('new_users'),
                'total_pageviews' => $analytics->sum('pageviews'),
                'avg_session_duration' => $analytics->avg('average_session_duration'),
                'avg_bounce_rate' => $analytics->avg('bounce_rate') * 100,
                'avg_engagement_rate' => $analytics->avg('engagement_rate') * 100,
                'total_conversions' => $analytics->sum('conversions'),
                'total_revenue' => $analytics->sum('total_revenue'),
            ];
            
            // Get traffic sources
            $trafficSources = GoogleAnalyticsData::where('google_analytics_property_id', $property->id)
                ->where('dimension_type', 'source')
                ->where('date', '>=', $startDate)
                ->selectRaw('dimension_value, SUM(sessions) as total_sessions')
                ->groupBy('dimension_value')
                ->orderByDesc('total_sessions')
                ->limit(5)
                ->get();
            
            // Get top pages
            $topPages = GoogleAnalyticsData::where('google_analytics_property_id', $property->id)
                ->where('dimension_type', 'page')
                ->where('date', '>=', $startDate)
                ->selectRaw('dimension_value, SUM(pageviews) as total_pageviews')
                ->groupBy('dimension_value')
                ->orderByDesc('total_pageviews')
                ->limit(10)
                ->get();
            
            $propertyData = $property;
        }
        
        return view('analytics.google-analytics', compact(
            'accounts',
            'selectedAccount',
            'propertyData',
            'analyticsData',
            'chartData',
            'trafficSources',
            'topPages',
            'days'
        ));
    }
}

