<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Models\FacebookPage;
use App\Models\YouTubeChannel;
use App\Models\InstagramAccount;
use App\Models\GoogleAnalyticsProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Facebook OAuth
    |--------------------------------------------------------------------------
    */
    
    public function facebookRedirect()
    {
        $clientId = config('services.facebook.client_id');
        $redirectUri = url(config('services.facebook.redirect'));
        $scopes = implode(',', config('services.facebook.scopes'));
        $state = Str::random(40);
        
        session(['oauth_state' => $state]);
        
        $url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'response_type' => 'code',
        ]);
        
        return redirect($url);
    }
    
    public function facebookCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Facebook authorization was cancelled.');
        }
        
        // Verify state
        if ($request->state !== session('oauth_state')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Invalid state parameter.');
        }
        
        try {
            // Exchange code for access token
            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'redirect_uri' => url(config('services.facebook.redirect')),
                'code' => $request->code,
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to get access token');
            }
            
            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'];
            
            // Get long-lived token
            $longLivedResponse = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'fb_exchange_token' => $accessToken,
            ]);
            
            if ($longLivedResponse->successful()) {
                $longLivedData = $longLivedResponse->json();
                $accessToken = $longLivedData['access_token'];
                $expiresIn = $longLivedData['expires_in'] ?? 5184000; // Default 60 days
            }
            
            // Get user info
            $userResponse = Http::get('https://graph.facebook.com/v18.0/me', [
                'access_token' => $accessToken,
                'fields' => 'id,name',
            ]);
            
            $userData = $userResponse->json();
            
            // Get pages the user manages
            $pagesResponse = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,access_token,category,followers_count,fan_count,picture,cover,about,website',
            ]);
            
            $pagesData = $pagesResponse->json();
            
            if (empty($pagesData['data'])) {
                return redirect()->route('integrations.index')
                    ->with('error', 'No Facebook Pages found. Make sure you have admin access to at least one Page.');
            }
            
            // Save each page as a social account
            foreach ($pagesData['data'] as $page) {
                $socialAccount = SocialAccount::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'platform' => 'facebook',
                        'platform_account_id' => $page['id'],
                    ],
                    [
                        'platform_user_id' => $userData['id'],
                        'account_name' => $page['name'],
                        'account_type' => 'page',
                        'access_token' => $page['access_token'],
                        'token_expires_at' => now()->addSeconds($expiresIn ?? 5184000),
                        'scopes' => implode(',', config('services.facebook.scopes')),
                        'account_data' => [
                            'category' => $page['category'] ?? null,
                            'picture' => $page['picture']['data']['url'] ?? null,
                        ],
                        'status' => 'active',
                    ]
                );
                
                // Create or update Facebook Page record
                FacebookPage::updateOrCreate(
                    [
                        'social_account_id' => $socialAccount->id,
                        'page_id' => $page['id'],
                    ],
                    [
                        'name' => $page['name'],
                        'category' => $page['category'] ?? null,
                        'followers_count' => $page['followers_count'] ?? 0,
                        'likes_count' => $page['fan_count'] ?? 0,
                        'profile_picture_url' => $page['picture']['data']['url'] ?? null,
                        'cover_photo_url' => $page['cover']['source'] ?? null,
                        'about' => $page['about'] ?? null,
                        'website' => $page['website'] ?? null,
                    ]
                );
            }
            
            return redirect()->route('integrations.index')
                ->with('success', 'Facebook Pages connected successfully!');
                
        } catch (\Exception $e) {
            Log::error('Facebook OAuth Error: ' . $e->getMessage());
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect Facebook: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | YouTube OAuth
    |--------------------------------------------------------------------------
    */
    
    public function youtubeRedirect()
    {
        $clientId = config('services.youtube.client_id');
        $redirectUri = url(config('services.youtube.redirect'));
        $scopes = implode(' ', config('services.youtube.scopes'));
        $state = Str::random(40);
        
        session(['oauth_state' => $state]);
        
        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
        
        return redirect($url);
    }
    
    public function youtubeCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('integrations.index')
                ->with('error', 'YouTube authorization was cancelled.');
        }
        
        if ($request->state !== session('oauth_state')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Invalid state parameter.');
        }
        
        try {
            // Exchange code for tokens
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
                'redirect_uri' => url(config('services.youtube.redirect')),
                'code' => $request->code,
                'grant_type' => 'authorization_code',
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to get access token');
            }
            
            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? 3600;
            
            // Get channel info
            $channelResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics,brandingSettings',
                    'mine' => 'true',
                ]);
            
            $channelData = $channelResponse->json();
            
            if (empty($channelData['items'])) {
                return redirect()->route('integrations.index')
                    ->with('error', 'No YouTube channel found for this account.');
            }
            
            $channel = $channelData['items'][0];
            
            // Create social account
            $socialAccount = SocialAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'platform' => 'youtube',
                    'platform_account_id' => $channel['id'],
                ],
                [
                    'platform_user_id' => $channel['id'],
                    'account_name' => $channel['snippet']['title'],
                    'account_type' => 'channel',
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'scopes' => implode(' ', config('services.youtube.scopes')),
                    'account_data' => [
                        'thumbnail' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
                    ],
                    'status' => 'active',
                ]
            );
            
            // Create or update YouTube Channel record
            YouTubeChannel::updateOrCreate(
                [
                    'social_account_id' => $socialAccount->id,
                    'channel_id' => $channel['id'],
                ],
                [
                    'title' => $channel['snippet']['title'],
                    'description' => $channel['snippet']['description'] ?? null,
                    'custom_url' => $channel['snippet']['customUrl'] ?? null,
                    'thumbnail_url' => $channel['snippet']['thumbnails']['high']['url'] ?? null,
                    'banner_url' => $channel['brandingSettings']['image']['bannerExternalUrl'] ?? null,
                    'country' => $channel['snippet']['country'] ?? null,
                    'subscriber_count' => $channel['statistics']['subscriberCount'] ?? 0,
                    'video_count' => $channel['statistics']['videoCount'] ?? 0,
                    'view_count' => $channel['statistics']['viewCount'] ?? 0,
                    'published_at' => $channel['snippet']['publishedAt'] ?? null,
                ]
            );
            
            return redirect()->route('integrations.index')
                ->with('success', 'YouTube channel connected successfully!');
                
        } catch (\Exception $e) {
            Log::error('YouTube OAuth Error: ' . $e->getMessage());
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect YouTube: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Instagram OAuth
    |--------------------------------------------------------------------------
    */
    
    public function instagramRedirect()
    {
        $clientId = config('services.instagram.client_id');
        $redirectUri = url(config('services.instagram.redirect'));
        $scopes = implode(',', config('services.instagram.scopes'));
        $state = Str::random(40);
        
        session(['oauth_state' => $state]);
        
        // Instagram Business accounts use Facebook Login
        $url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'response_type' => 'code',
        ]);
        
        return redirect($url);
    }
    
    public function instagramCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Instagram authorization was cancelled.');
        }
        
        if ($request->state !== session('oauth_state')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Invalid state parameter.');
        }
        
        try {
            // Exchange code for access token (same as Facebook)
            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id' => config('services.instagram.client_id'),
                'client_secret' => config('services.instagram.client_secret'),
                'redirect_uri' => url(config('services.instagram.redirect')),
                'code' => $request->code,
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to get access token');
            }
            
            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'];
            
            // Get pages with Instagram accounts
            $pagesResponse = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,instagram_business_account{id,username,name,profile_picture_url,followers_count,follows_count,media_count,biography,website}',
            ]);
            
            $pagesData = $pagesResponse->json();
            $connectedCount = 0;
            
            foreach ($pagesData['data'] ?? [] as $page) {
                if (!isset($page['instagram_business_account'])) {
                    continue;
                }
                
                $igAccount = $page['instagram_business_account'];
                
                // Get page access token for this Instagram account
                $pageTokenResponse = Http::get("https://graph.facebook.com/v18.0/{$page['id']}", [
                    'access_token' => $accessToken,
                    'fields' => 'access_token',
                ]);
                
                $pageAccessToken = $pageTokenResponse->json()['access_token'] ?? $accessToken;
                
                $socialAccount = SocialAccount::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'platform' => 'instagram',
                        'platform_account_id' => $igAccount['id'],
                    ],
                    [
                        'platform_user_id' => $igAccount['id'],
                        'account_name' => $igAccount['username'],
                        'account_type' => 'business',
                        'access_token' => $pageAccessToken,
                        'token_expires_at' => now()->addDays(60),
                        'scopes' => implode(',', config('services.instagram.scopes')),
                        'account_data' => [
                            'facebook_page_id' => $page['id'],
                            'profile_picture' => $igAccount['profile_picture_url'] ?? null,
                        ],
                        'status' => 'active',
                    ]
                );
                
                InstagramAccount::updateOrCreate(
                    [
                        'social_account_id' => $socialAccount->id,
                        'instagram_id' => $igAccount['id'],
                    ],
                    [
                        'username' => $igAccount['username'],
                        'name' => $igAccount['name'] ?? null,
                        'biography' => $igAccount['biography'] ?? null,
                        'profile_picture_url' => $igAccount['profile_picture_url'] ?? null,
                        'website' => $igAccount['website'] ?? null,
                        'followers_count' => $igAccount['followers_count'] ?? 0,
                        'follows_count' => $igAccount['follows_count'] ?? 0,
                        'media_count' => $igAccount['media_count'] ?? 0,
                        'account_type' => 'business',
                    ]
                );
                
                $connectedCount++;
            }
            
            if ($connectedCount === 0) {
                return redirect()->route('integrations.index')
                    ->with('error', 'No Instagram Business accounts found. Make sure your Instagram account is connected to a Facebook Page.');
            }
            
            return redirect()->route('integrations.index')
                ->with('success', "{$connectedCount} Instagram account(s) connected successfully!");
                
        } catch (\Exception $e) {
            Log::error('Instagram OAuth Error: ' . $e->getMessage());
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect Instagram: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Google Analytics OAuth
    |--------------------------------------------------------------------------
    */
    
    public function googleAnalyticsRedirect()
    {
        $clientId = config('services.google_analytics.client_id');
        $redirectUri = url(config('services.google_analytics.redirect'));
        $scopes = implode(' ', config('services.google_analytics.scopes'));
        $state = Str::random(40);
        
        session(['oauth_state' => $state]);
        
        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
        
        return redirect($url);
    }
    
    public function googleAnalyticsCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Google Analytics authorization was cancelled.');
        }
        
        if ($request->state !== session('oauth_state')) {
            return redirect()->route('integrations.index')
                ->with('error', 'Invalid state parameter.');
        }
        
        try {
            // Exchange code for tokens
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google_analytics.client_id'),
                'client_secret' => config('services.google_analytics.client_secret'),
                'redirect_uri' => url(config('services.google_analytics.redirect')),
                'code' => $request->code,
                'grant_type' => 'authorization_code',
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to get access token');
            }
            
            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? 3600;
            
            // Get GA4 properties
            $accountsResponse = Http::withToken($accessToken)
                ->get('https://analyticsadmin.googleapis.com/v1beta/accountSummaries');
            
            $accountsData = $accountsResponse->json();
            $connectedCount = 0;
            
            foreach ($accountsData['accountSummaries'] ?? [] as $account) {
                foreach ($account['propertySummaries'] ?? [] as $property) {
                    $propertyId = str_replace('properties/', '', $property['property']);
                    
                    $socialAccount = SocialAccount::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'platform' => 'google_analytics',
                            'platform_account_id' => $propertyId,
                        ],
                        [
                            'platform_user_id' => $account['account'],
                            'account_name' => $property['displayName'],
                            'account_type' => 'property',
                            'access_token' => $accessToken,
                            'refresh_token' => $refreshToken,
                            'token_expires_at' => now()->addSeconds($expiresIn),
                            'scopes' => implode(' ', config('services.google_analytics.scopes')),
                            'account_data' => [
                                'account_name' => $account['displayName'] ?? null,
                            ],
                            'status' => 'active',
                        ]
                    );
                    
                    GoogleAnalyticsProperty::updateOrCreate(
                        [
                            'social_account_id' => $socialAccount->id,
                            'property_id' => $propertyId,
                        ],
                        [
                            'property_name' => $property['displayName'],
                            'property_type' => 'GA4',
                            'account_id' => str_replace('accounts/', '', $account['account']),
                            'account_name' => $account['displayName'] ?? null,
                        ]
                    );
                    
                    $connectedCount++;
                }
            }
            
            if ($connectedCount === 0) {
                return redirect()->route('integrations.index')
                    ->with('error', 'No Google Analytics properties found.');
            }
            
            return redirect()->route('integrations.index')
                ->with('success', "{$connectedCount} Google Analytics property(s) connected successfully!");
                
        } catch (\Exception $e) {
            Log::error('Google Analytics OAuth Error: ' . $e->getMessage());
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect Google Analytics: ' . $e->getMessage());
        }
    }
}

