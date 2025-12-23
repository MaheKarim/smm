@extends('layouts.app')

@section('title', 'Reconnect Account')
@section('breadcrumb-parent', 'Integrations')
@section('breadcrumb', 'Reconnect ' . $account->account_name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="text-center">
        <div class="w-20 h-20 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-amber-500/10">
            <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Reconnect Account</h1>
        <p class="text-[var(--text-secondary)] mt-2">
            The access token for <strong>{{ $account->account_name }}</strong> has expired or is invalid.
        </p>
    </div>

    <!-- Account Info -->
    <div class="card p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center"
                 style="background-color: {{ $platformConfig['color'] }}20;">
                @include('integrations.partials.platform-icon', ['platform' => $platform, 'class' => 'w-7 h-7', 'color' => $platformConfig['color']])
            </div>
            <div>
                <h3 class="font-semibold text-[var(--text-primary)]">{{ $account->account_name }}</h3>
                <p class="text-sm text-[var(--text-muted)]">
                    {{ $platformConfig['name'] }} • ID: {{ Str::limit($account->platform_account_id, 25) }}
                </p>
            </div>
        </div>

        <div class="border-t border-[var(--border)] pt-6">
            <h4 class="font-medium text-[var(--text-primary)] mb-4">Choose a reconnection method:</h4>

            <!-- OAuth Reconnection -->
            <div class="mb-6">
                <a href="{{ route('integrations.' . str_replace('_', '-', $platform) . '.connect') }}" 
                   class="flex items-center justify-between p-4 rounded-xl border border-[var(--border)] hover:border-[var(--primary)] hover:bg-[var(--surface-hover)] transition-colors group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-[var(--primary)]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[var(--primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-[var(--text-primary)]">Reconnect with OAuth</p>
                            <p class="text-sm text-[var(--text-muted)]">Recommended - Reauthorize through {{ $platformConfig['name'] }}</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-[var(--text-muted)] group-hover:text-[var(--primary)] group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <!-- Manual Token Update -->
            <div class="border-t border-[var(--border)] pt-6">
                <h5 class="text-sm font-medium text-[var(--text-primary)] mb-4">Or update token manually:</h5>
                
                <form action="{{ route('integrations.update-token', $account) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">New Access Token</label>
                        <textarea name="access_token" required rows="4"
                                  class="input font-mono text-sm" placeholder="Paste your new access token here..."></textarea>
                        <p class="text-xs text-[var(--text-muted)] mt-1">
                            Generate a new token from the {{ $platformConfig['name'] }} developer console.
                        </p>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Update Token
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Links -->
    <div class="card p-6">
        <h4 class="font-medium text-[var(--text-primary)] mb-4">Need help?</h4>
        <div class="space-y-3">
            @if($platform === 'facebook' || $platform === 'instagram')
            <a href="https://developers.facebook.com/tools/explorer/" target="_blank" 
               class="flex items-center gap-3 text-sm text-[var(--text-secondary)] hover:text-[var(--primary)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Facebook Graph API Explorer
            </a>
            @endif
            
            @if($platform === 'youtube' || $platform === 'google_analytics')
            <a href="https://console.cloud.google.com/" target="_blank" 
               class="flex items-center gap-3 text-sm text-[var(--text-secondary)] hover:text-[var(--primary)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Google Cloud Console
            </a>
            @endif
            
            <a href="#" class="flex items-center gap-3 text-sm text-[var(--text-secondary)] hover:text-[var(--primary)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Documentation & FAQ
            </a>
        </div>
    </div>

    <!-- Back Link -->
    <div class="text-center">
        <a href="{{ route('integrations.show', $platform) }}" class="text-sm text-[var(--text-muted)] hover:text-[var(--primary)]">
            ← Back to {{ $platformConfig['name'] }} Integration
        </a>
    </div>
</div>
@endsection

