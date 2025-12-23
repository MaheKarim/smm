<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SyncLog;
use App\Jobs\SyncFacebookDataJob;
use App\Jobs\SyncYouTubeDataJob;
use App\Jobs\SyncInstagramDataJob;
use App\Jobs\SyncGoogleAnalyticsDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class IntegrationController extends Controller
{
    /**
     * Platform configurations with connection methods
     */
    protected array $platforms = [
        'facebook' => [
            'name' => 'Facebook Pages',
            'icon' => 'facebook',
            'color' => '#1877f2',
            'description' => 'Connect Facebook Pages to track engagement, reach, and post performance.',
            'connection_methods' => ['oauth', 'page_token'],
            'features' => ['Page Insights', 'Post Analytics', 'Video Metrics', 'Ad Detection'],
            'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_read_user_content', 'read_insights', 'ads_read'],
        ],
        'youtube' => [
            'name' => 'YouTube Channels',
            'icon' => 'youtube',
            'color' => '#ff0000',
            'description' => 'Connect YouTube channels to monitor subscribers, views, and video performance.',
            'connection_methods' => ['oauth', 'api_key'],
            'features' => ['Channel Stats', 'Video Analytics', 'Revenue Tracking', 'Audience Retention'],
            'scopes' => ['youtube.readonly', 'yt-analytics.readonly', 'yt-analytics-monetary.readonly'],
        ],
        'instagram' => [
            'name' => 'Instagram Business',
            'icon' => 'instagram',
            'color' => '#e4405f',
            'description' => 'Connect Instagram Business accounts for insights on posts, stories, and reels.',
            'connection_methods' => ['oauth'],
            'features' => ['Profile Insights', 'Post Performance', 'Story Analytics', 'Hashtag Tracking'],
            'scopes' => ['instagram_basic', 'instagram_manage_insights', 'pages_show_list'],
        ],
        'google_analytics' => [
            'name' => 'Google Analytics',
            'icon' => 'google',
            'color' => '#f9ab00',
            'description' => 'Connect GA4 properties to track website traffic and user behavior.',
            'connection_methods' => ['oauth', 'service_account'],
            'features' => ['Traffic Analysis', 'User Behavior', 'Conversions', 'Real-time Data'],
            'scopes' => ['analytics.readonly'],
        ],
    ];

    /**
     * Display the integrations dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        $accounts = SocialAccount::where('user_id', $user->id)
            ->orderBy('platform')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('platform');

        $syncLogs = SyncLog::where('user_id', $user->id)
            ->with('socialAccount')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get platform health status
        $platformHealth = $this->getPlatformHealth($accounts);
        
        // Get last sync times
        $lastSyncTimes = $this->getLastSyncTimes($user->id);

        return view('integrations.index', compact(
            'accounts', 
            'syncLogs', 
            'platformHealth',
            'lastSyncTimes'
        ))->with('platforms', $this->platforms);
    }

    /**
     * Show platform-specific integration page
     */
    public function show(string $platform)
    {
        if (!isset($this->platforms[$platform])) {
            abort(404, 'Platform not found');
        }

        $user = Auth::user();
        $platformConfig = $this->platforms[$platform];
        
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('platform', $platform)
            ->orderBy('created_at', 'desc')
            ->get();

        $syncLogs = SyncLog::where('user_id', $user->id)
            ->where('platform', $platform)
            ->with('socialAccount')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Check for accounts needing reauthorization
        $needsReauth = $accounts->filter(fn($a) => $a->isTokenExpired() || $a->status === 'expired');

        return view('integrations.platform', compact(
            'platform',
            'platformConfig',
            'accounts',
            'syncLogs',
            'needsReauth'
        ));
    }

    /**
     * Connect account via manual token input
     */
    public function connectWithToken(Request $request, string $platform)
    {
        $rules = [
            'access_token' => 'required|string',
            'account_name' => 'nullable|string|max:255',
        ];

        // Platform-specific validation
        if ($platform === 'facebook') {
            $rules['page_id'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $user = Auth::user();
        
        // Validate the token by making a test API call
        $pageId = $request->input('page_id');
        $validation = $this->validateToken($platform, $request->access_token, $pageId);
        
        if (!$validation['valid']) {
            return back()
                ->withInput()
                ->with('error', 'Invalid access token: ' . $validation['message']);
        }

        // Check if this account is already connected
        $existingAccount = SocialAccount::where('user_id', $user->id)
            ->where('platform', $platform)
            ->where('platform_account_id', $validation['account_id'] ?? $pageId)
            ->first();

        if ($existingAccount) {
            // Update existing account instead of creating new
            $existingAccount->update([
                'access_token' => $request->access_token,
                'token_expires_at' => $validation['expires_at'] ?? null,
                'status' => 'active',
                'account_data' => array_merge($existingAccount->account_data ?? [], $validation['account_data'] ?? []),
            ]);

            $this->triggerSync($existingAccount);

            return redirect()->route('integrations.show', $platform)
                ->with('success', "Token updated for {$existingAccount->account_name}!");
        }

        // Use validated account name or fall back to user provided
        $accountName = $validation['account_name'] ?? $request->account_name ?? 'Connected Account';

        // Create the social account
        $account = SocialAccount::create([
            'user_id' => $user->id,
            'platform' => $platform,
            'platform_user_id' => $validation['user_id'] ?? $pageId ?? uniqid(),
            'platform_account_id' => $validation['account_id'] ?? $pageId ?? uniqid(),
            'account_name' => $accountName,
            'account_type' => $validation['account_type'] ?? 'page_token',
            'access_token' => $request->access_token,
            'token_expires_at' => $validation['expires_at'] ?? null,
            'account_data' => array_merge($validation['account_data'] ?? [], [
                'connection_method' => 'page_token',
            ]),
            'status' => 'active',
        ]);

        // Trigger initial sync
        $this->triggerSync($account);

        return redirect()->route('integrations.show', $platform)
            ->with('success', "Successfully connected {$accountName}!");
    }

    /**
     * Connect via API Key (for platforms that support it)
     */
    public function connectWithApiKey(Request $request, string $platform)
    {
        $request->validate([
            'api_key' => 'required|string',
            'account_name' => 'required|string|max:255',
            'property_id' => 'nullable|string|max:255', // For GA
            'channel_id' => 'nullable|string|max:255', // For YouTube
        ]);

        $user = Auth::user();
        
        // Validate API key
        $validation = $this->validateApiKey($platform, $request->api_key, $request->all());
        
        if (!$validation['valid']) {
            return back()->with('error', 'Invalid API key: ' . $validation['message']);
        }

        $account = SocialAccount::create([
            'user_id' => $user->id,
            'platform' => $platform,
            'platform_user_id' => $validation['user_id'] ?? $request->channel_id ?? $request->property_id,
            'platform_account_id' => $request->channel_id ?? $request->property_id ?? uniqid(),
            'account_name' => $request->account_name,
            'account_type' => 'api_key',
            'access_token' => $request->api_key,
            'account_data' => array_merge($validation['account_data'] ?? [], [
                'connection_method' => 'api_key',
            ]),
            'status' => 'active',
        ]);

        $this->triggerSync($account);

        return redirect()->route('integrations.show', $platform)
            ->with('success', "Successfully connected via API key!");
    }

    /**
     * Connect via Service Account (for Google services)
     */
    public function connectWithServiceAccount(Request $request, string $platform)
    {
        $request->validate([
            'service_account_json' => 'required|file|mimes:json',
            'property_id' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        
        // Parse and validate service account JSON
        $jsonContent = file_get_contents($request->file('service_account_json')->path());
        $serviceAccount = json_decode($jsonContent, true);
        
        if (!$serviceAccount || !isset($serviceAccount['client_email'])) {
            return back()->with('error', 'Invalid service account JSON file');
        }

        // Store encrypted credentials
        $account = SocialAccount::create([
            'user_id' => $user->id,
            'platform' => $platform,
            'platform_user_id' => $serviceAccount['client_email'],
            'platform_account_id' => $request->property_id,
            'account_name' => $request->account_name,
            'account_type' => 'service_account',
            'access_token' => Crypt::encryptString($jsonContent),
            'account_data' => [
                'connection_method' => 'service_account',
                'property_id' => $request->property_id,
                'client_email' => $serviceAccount['client_email'],
                'project_id' => $serviceAccount['project_id'] ?? null,
            ],
            'status' => 'active',
        ]);

        $this->triggerSync($account);

        return redirect()->route('integrations.show', $platform)
            ->with('success', "Successfully connected via service account!");
    }

    /**
     * Refresh/reconnect an expired account
     */
    public function reconnect(SocialAccount $socialAccount)
    {
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $platform = $socialAccount->platform;
        $connectionMethod = $socialAccount->account_data['connection_method'] ?? 'oauth';

        if ($connectionMethod === 'oauth') {
            // Redirect to OAuth flow with reconnect flag
            session(['reconnect_account_id' => $socialAccount->id]);
            return redirect()->route("integrations.{$platform}.connect");
        }

        // For manual tokens, show the reconnect form
        return view('integrations.reconnect', [
            'account' => $socialAccount,
            'platform' => $platform,
            'platformConfig' => $this->platforms[$platform],
        ]);
    }

    /**
     * Update token for manual connection
     */
    public function updateToken(Request $request, SocialAccount $socialAccount)
    {
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'access_token' => 'required|string',
        ]);

        // Get existing page ID if any
        $pageId = $socialAccount->account_data['page_id'] ?? $socialAccount->platform_account_id ?? null;

        // Validate the new token
        $validation = $this->validateToken($socialAccount->platform, $request->access_token, $pageId);
        
        if (!$validation['valid']) {
            return back()
                ->withInput()
                ->with('error', 'Invalid access token: ' . $validation['message']);
        }

        $socialAccount->update([
            'access_token' => $request->access_token,
            'token_expires_at' => $validation['expires_at'] ?? null,
            'status' => 'active',
            'account_data' => array_merge($socialAccount->account_data ?? [], $validation['account_data'] ?? []),
        ]);

        $this->triggerSync($socialAccount);

        return redirect()->route('integrations.show', $socialAccount->platform)
            ->with('success', 'Token updated successfully!');
    }

    /**
     * Test connection health
     */
    public function testConnection(SocialAccount $socialAccount)
    {
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $health = $this->checkAccountHealth($socialAccount);

        return response()->json([
            'status' => $health['healthy'] ? 'healthy' : 'unhealthy',
            'message' => $health['message'],
            'last_checked' => now()->toISOString(),
            'details' => $health['details'] ?? null,
        ]);
    }

    /**
     * Disconnect an account
     */
    public function disconnect(SocialAccount $socialAccount)
    {
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $platformName = ucfirst(str_replace('_', ' ', $socialAccount->platform));
        $accountName = $socialAccount->account_name;

        // Soft delete - mark as disconnected first
        $socialAccount->update(['status' => 'disconnected']);
        
        // Then hard delete
        $socialAccount->delete();

        return redirect()->route('integrations.index')
            ->with('success', "{$platformName} account '{$accountName}' has been disconnected.");
    }

    /**
     * Sync a single account
     */
    public function sync(SocialAccount $socialAccount)
    {
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        // Check rate limit
        $rateLimitKey = "sync_limit_{$socialAccount->id}";
        if (Cache::has($rateLimitKey)) {
            $retryAfter = Cache::get($rateLimitKey) - now()->timestamp;
            return back()->with('error', "Please wait {$retryAfter} seconds before syncing again.");
        }

        // Set rate limit (1 sync per minute per account)
        Cache::put($rateLimitKey, now()->addMinute()->timestamp, 60);

        $this->triggerSync($socialAccount);

        return redirect()->back()
            ->with('success', 'Sync started for ' . $socialAccount->account_name);
    }

    /**
     * Sync all accounts
     */
    public function syncAll()
    {
        $user = Auth::user();
        
        // Check rate limit
        $rateLimitKey = "sync_all_limit_{$user->id}";
        if (Cache::has($rateLimitKey)) {
            $retryAfter = Cache::get($rateLimitKey) - now()->timestamp;
            return back()->with('error', "Please wait {$retryAfter} seconds before syncing all accounts again.");
        }

        // Set rate limit (1 sync all per 5 minutes)
        Cache::put($rateLimitKey, now()->addMinutes(5)->timestamp, 300);

        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $syncCount = 0;
        foreach ($accounts as $account) {
            $this->triggerSync($account, 'manual');
            $syncCount++;
        }

        return redirect()->route('dashboard')
            ->with('success', "Sync started for {$syncCount} connected accounts.");
    }

    /**
     * Instant refresh - sync with bypass cache
     */
    public function instantRefresh(Request $request)
    {
        $user = Auth::user();
        
        // Check rate limit (1 per 5 minutes)
        $rateLimitKey = "instant_refresh_{$user->id}";
        if (Cache::has($rateLimitKey)) {
            return response()->json([
                'status' => 'rate_limited',
                'retry_after' => Cache::get($rateLimitKey) - now()->timestamp,
            ], 429);
        }

        Cache::put($rateLimitKey, now()->addMinutes(5)->timestamp, 300);

        $platforms = $request->input('platforms', ['facebook', 'youtube', 'instagram', 'google_analytics']);
        
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('platform', $platforms)
            ->get();

        $jobId = uniqid('sync_');
        
        foreach ($accounts as $account) {
            $syncLog = SyncLog::create([
                'user_id' => $user->id,
                'social_account_id' => $account->id,
                'platform' => $account->platform,
                'sync_type' => 'instant',
                'status' => 'pending',
                'metadata' => ['job_id' => $jobId],
            ]);

            $this->dispatchSyncJob($account, $syncLog, true);
        }

        return response()->json([
            'status' => 'pending',
            'job_id' => $jobId,
            'accounts_queued' => $accounts->count(),
        ]);
    }

    /**
     * Check sync status
     */
    public function syncStatus(string $jobId)
    {
        $logs = SyncLog::where('metadata->job_id', $jobId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $total = $logs->count();
        $completed = $logs->where('status', 'completed')->count();
        $failed = $logs->where('status', 'failed')->count();
        $pending = $logs->whereIn('status', ['pending', 'running'])->count();

        $status = 'processing';
        if ($pending === 0) {
            $status = $failed === $total ? 'failed' : 'completed';
        }

        return response()->json([
            'status' => $status,
            'progress' => $total > 0 ? round((($completed + $failed) / $total) * 100) : 0,
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
        ]);
    }

    /**
     * Get sync logs for an account
     */
    public function syncLogs(SocialAccount $socialAccount)
    {
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $logs = SyncLog::where('social_account_id', $socialAccount->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    /**
     * Validate access token for a platform
     */
    protected function validateToken(string $platform, string $token, ?string $pageId = null): array
    {
        try {
            switch ($platform) {
                case 'facebook':
                    return $this->validateFacebookToken($token, $pageId);

                case 'youtube':
                    $response = Http::get("https://www.googleapis.com/youtube/v3/channels", [
                        'access_token' => $token,
                        'part' => 'snippet',
                        'mine' => 'true',
                    ]);
                    if ($response->successful() && !empty($response->json()['items'])) {
                        $channel = $response->json()['items'][0];
                        return [
                            'valid' => true,
                            'user_id' => $channel['id'],
                            'account_id' => $channel['id'],
                            'account_data' => [
                                'title' => $channel['snippet']['title'],
                                'thumbnail' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
                            ],
                        ];
                    }
                    return ['valid' => false, 'message' => 'Invalid token or no channels found'];

                case 'instagram':
                    // Instagram uses Facebook OAuth
                    $response = Http::get("https://graph.facebook.com/v18.0/me/accounts", [
                        'access_token' => $token,
                        'fields' => 'id,name,instagram_business_account',
                    ]);
                    if ($response->successful()) {
                        $pages = collect($response->json()['data'] ?? [])
                            ->filter(fn($p) => isset($p['instagram_business_account']));
                        if ($pages->isNotEmpty()) {
                            return [
                                'valid' => true,
                                'account_data' => ['pages' => $pages->values()->all()],
                            ];
                        }
                        return ['valid' => false, 'message' => 'No Instagram Business accounts linked'];
                    }
                    return ['valid' => false, 'message' => 'Invalid token'];

                case 'google_analytics':
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer {$token}",
                    ])->get("https://analyticsadmin.googleapis.com/v1beta/accountSummaries");
                    if ($response->successful()) {
                        return [
                            'valid' => true,
                            'account_data' => $response->json(),
                        ];
                    }
                    return ['valid' => false, 'message' => 'Invalid token'];

                default:
                    return ['valid' => false, 'message' => 'Unknown platform'];
            }
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Validate API key
     */
    protected function validateApiKey(string $platform, string $apiKey, array $params): array
    {
        try {
            switch ($platform) {
                case 'youtube':
                    $channelId = $params['channel_id'] ?? null;
                    if (!$channelId) {
                        return ['valid' => false, 'message' => 'Channel ID is required'];
                    }
                    $response = Http::get("https://www.googleapis.com/youtube/v3/channels", [
                        'key' => $apiKey,
                        'id' => $channelId,
                        'part' => 'snippet,statistics',
                    ]);
                    if ($response->successful() && !empty($response->json()['items'])) {
                        $channel = $response->json()['items'][0];
                        return [
                            'valid' => true,
                            'user_id' => $channel['id'],
                            'account_data' => [
                                'title' => $channel['snippet']['title'],
                                'subscribers' => $channel['statistics']['subscriberCount'] ?? 0,
                            ],
                        ];
                    }
                    return ['valid' => false, 'message' => 'Invalid API key or channel not found'];

                default:
                    return ['valid' => false, 'message' => 'API key not supported for this platform'];
            }
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check account health
     */
    protected function checkAccountHealth(SocialAccount $account): array
    {
        // Check token expiration
        if ($account->isTokenExpired()) {
            return [
                'healthy' => false,
                'message' => 'Access token has expired',
                'action' => 'reconnect',
            ];
        }

        // Check last sync
        $lastSync = $account->syncLogs()->latest()->first();
        if ($lastSync && $lastSync->status === 'failed') {
            return [
                'healthy' => false,
                'message' => 'Last sync failed: ' . ($lastSync->error_message ?? 'Unknown error'),
                'action' => 'retry',
            ];
        }

        // Try a test API call
        $validation = $this->validateToken($account->platform, $account->access_token);
        
        if (!$validation['valid']) {
            return [
                'healthy' => false,
                'message' => $validation['message'],
                'action' => 'reconnect',
            ];
        }

        return [
            'healthy' => true,
            'message' => 'Connection is healthy',
        ];
    }

    /**
     * Get platform health summary
     */
    protected function getPlatformHealth($accounts): array
    {
        $health = [];
        
        foreach ($this->platforms as $platform => $config) {
            $platformAccounts = $accounts->get($platform, collect());
            $healthy = $platformAccounts->filter(fn($a) => $a->isActive())->count();
            $unhealthy = $platformAccounts->count() - $healthy;
            
            $health[$platform] = [
                'total' => $platformAccounts->count(),
                'healthy' => $healthy,
                'unhealthy' => $unhealthy,
                'status' => $unhealthy > 0 ? 'warning' : ($healthy > 0 ? 'healthy' : 'none'),
            ];
        }
        
        return $health;
    }

    /**
     * Get last sync times
     */
    protected function getLastSyncTimes(int $userId): array
    {
        return SyncLog::where('user_id', $userId)
            ->where('status', 'completed')
            ->selectRaw('platform, MAX(completed_at) as last_sync')
            ->groupBy('platform')
            ->pluck('last_sync', 'platform')
            ->toArray();
    }

    /**
     * Trigger sync for an account
     */
    protected function triggerSync(SocialAccount $account, string $type = 'manual'): void
    {
        $syncLog = SyncLog::create([
            'user_id' => $account->user_id,
            'social_account_id' => $account->id,
            'platform' => $account->platform,
            'sync_type' => $type,
            'status' => 'pending',
        ]);

        $this->dispatchSyncJob($account, $syncLog);
    }

    /**
     * Dispatch sync job
     */
    protected function dispatchSyncJob(SocialAccount $account, SyncLog $syncLog, bool $priority = false): void
    {
        $queue = $priority ? 'sync-priority' : 'default';
        
        $job = match ($account->platform) {
            'facebook' => new SyncFacebookDataJob($account, $syncLog),
            'youtube' => new SyncYouTubeDataJob($account, $syncLog),
            'instagram' => new SyncInstagramDataJob($account, $syncLog),
            'google_analytics' => new SyncGoogleAnalyticsDataJob($account, $syncLog),
            default => null,
        };

        if ($job) {
            dispatch($job)->onQueue($queue);
        }
    }

    /**
     * Validate Facebook access token (supports both User and Page tokens)
     */
    protected function validateFacebookToken(string $token, ?string $pageId = null): array
    {
        try {
            // First, try to get info about the token itself using /me
            // For a Page Access Token, /me returns the Page info
            // For a User Access Token, /me returns the User info
            $meResponse = Http::timeout(15)->get("https://graph.facebook.com/v18.0/me", [
                'access_token' => $token,
                'fields' => 'id,name,category,fan_count,followers_count,about',
            ]);

            if ($meResponse->failed()) {
                $error = $meResponse->json()['error'] ?? [];
                $errorCode = $error['code'] ?? 0;
                $errorMessage = $error['message'] ?? 'Invalid token';
                
                // Provide more helpful error messages
                if ($errorCode === 190) {
                    return ['valid' => false, 'message' => 'Access token has expired or is invalid. Please generate a new token.'];
                }
                if ($errorCode === 100) {
                    return ['valid' => false, 'message' => 'Invalid token format or missing permissions.'];
                }
                
                return ['valid' => false, 'message' => $errorMessage];
            }

            $meData = $meResponse->json();
            
            // Check if this is a Page token (has category field) or User token
            $isPageToken = isset($meData['category']);
            
            if ($isPageToken) {
                // This is a Page Access Token - great for analytics!
                return [
                    'valid' => true,
                    'user_id' => $meData['id'],
                    'account_id' => $meData['id'],
                    'account_name' => $meData['name'],
                    'account_type' => 'page',
                    'account_data' => [
                        'page_id' => $meData['id'],
                        'page_name' => $meData['name'],
                        'category' => $meData['category'] ?? null,
                        'fan_count' => $meData['fan_count'] ?? 0,
                        'followers_count' => $meData['followers_count'] ?? 0,
                        'about' => $meData['about'] ?? null,
                        'token_type' => 'page_access_token',
                    ],
                ];
            }

            // This is a User Access Token - need to get pages the user manages
            $pagesResponse = Http::timeout(15)->get("https://graph.facebook.com/v18.0/me/accounts", [
                'access_token' => $token,
                'fields' => 'id,name,access_token,category,fan_count,followers_count',
            ]);

            if ($pagesResponse->failed()) {
                return [
                    'valid' => false, 
                    'message' => 'Could not fetch pages. Make sure the token has pages_show_list and pages_read_engagement permissions.',
                ];
            }

            $pagesData = $pagesResponse->json()['data'] ?? [];
            
            if (empty($pagesData)) {
                return [
                    'valid' => false,
                    'message' => 'No Facebook Pages found. Make sure you have admin access to at least one Facebook Page.',
                ];
            }

            // If a specific page_id was provided, find that page
            if ($pageId) {
                $targetPage = collect($pagesData)->firstWhere('id', $pageId);
                if (!$targetPage) {
                    return [
                        'valid' => false,
                        'message' => "Page ID {$pageId} not found in your accessible pages.",
                    ];
                }
                $pagesData = [$targetPage];
            }

            // Return info about the first page (or specified page)
            $page = $pagesData[0];
            
            return [
                'valid' => true,
                'user_id' => $meData['id'],
                'account_id' => $page['id'],
                'account_name' => $page['name'],
                'account_type' => 'page',
                'account_data' => [
                    'page_id' => $page['id'],
                    'page_name' => $page['name'],
                    'page_access_token' => $page['access_token'] ?? null, // Page-specific token
                    'category' => $page['category'] ?? null,
                    'fan_count' => $page['fan_count'] ?? 0,
                    'followers_count' => $page['followers_count'] ?? 0,
                    'token_type' => 'user_access_token',
                    'available_pages' => collect($pagesData)->map(fn($p) => [
                        'id' => $p['id'],
                        'name' => $p['name'],
                    ])->toArray(),
                ],
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['valid' => false, 'message' => 'Connection timeout. Please check your internet connection.'];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Validation failed: ' . $e->getMessage()];
        }
    }
}
