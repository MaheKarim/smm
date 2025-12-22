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

class IntegrationController extends Controller
{
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

        return view('integrations.index', compact('accounts', 'syncLogs'));
    }

    public function disconnect(SocialAccount $socialAccount)
    {
        // Ensure the account belongs to the current user
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $platformName = ucfirst(str_replace('_', ' ', $socialAccount->platform));
        $accountName = $socialAccount->account_name;

        $socialAccount->update(['status' => 'disconnected']);
        $socialAccount->delete();

        return redirect()->route('integrations.index')
            ->with('success', "{$platformName} account '{$accountName}' has been disconnected.");
    }

    public function sync(SocialAccount $socialAccount)
    {
        // Ensure the account belongs to the current user
        if ($socialAccount->user_id !== Auth::id()) {
            abort(403);
        }

        // Create a sync log
        $syncLog = SyncLog::create([
            'user_id' => Auth::id(),
            'social_account_id' => $socialAccount->id,
            'platform' => $socialAccount->platform,
            'sync_type' => 'manual',
            'status' => 'pending',
        ]);

        // Dispatch the appropriate job
        $this->dispatchSyncJob($socialAccount, $syncLog);

        return redirect()->route('integrations.index')
            ->with('success', 'Sync started for ' . $socialAccount->account_name);
    }

    public function syncAll()
    {
        $user = Auth::user();
        $accounts = SocialAccount::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($accounts as $account) {
            $syncLog = SyncLog::create([
                'user_id' => $user->id,
                'social_account_id' => $account->id,
                'platform' => $account->platform,
                'sync_type' => 'manual',
                'status' => 'pending',
            ]);

            $this->dispatchSyncJob($account, $syncLog);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Sync started for all connected accounts.');
    }

    protected function dispatchSyncJob(SocialAccount $account, SyncLog $syncLog): void
    {
        switch ($account->platform) {
            case 'facebook':
                SyncFacebookDataJob::dispatch($account, $syncLog);
                break;
            case 'youtube':
                SyncYouTubeDataJob::dispatch($account, $syncLog);
                break;
            case 'instagram':
                SyncInstagramDataJob::dispatch($account, $syncLog);
                break;
            case 'google_analytics':
                SyncGoogleAnalyticsDataJob::dispatch($account, $syncLog);
                break;
        }
    }
}

