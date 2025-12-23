@extends('layouts.app')

@section('title', $platformConfig['name'] . ' Integration')
@section('breadcrumb-parent', 'Integrations')
@section('breadcrumb', $platformConfig['name'])

@section('content')
<div class="space-y-6" x-data="platformIntegration()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0"
                 style="background-color: {{ $platformConfig['color'] }};">
                @include('integrations.partials.platform-icon', ['platform' => $platform, 'class' => 'w-8 h-8 text-white'])
            </div>
            <div>
                <h1 class="text-2xl font-bold text-[var(--text-primary)]">{{ $platformConfig['name'] }}</h1>
                <p class="text-[var(--text-secondary)] mt-1">{{ $platformConfig['description'] }}</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach($platformConfig['features'] as $feature)
                        <span class="px-2 py-1 text-xs rounded-md bg-[var(--surface-alt)] text-[var(--text-secondary)]">
                            {{ $feature }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        
        <a href="{{ route('integrations.index') }}" class="btn btn-secondary shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Integrations
        </a>
    </div>

    <!-- Accounts Needing Reauthorization Warning -->
    @if($needsReauth->count() > 0)
    <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h4 class="font-medium text-amber-500">{{ $needsReauth->count() }} {{ Str::plural('account', $needsReauth->count()) }} needs reauthorization</h4>
                <p class="text-sm text-amber-500/80 mt-1">
                    Some accounts have expired tokens. Please reconnect them to continue syncing data.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Connected Accounts -->
    @if($accounts->count() > 0)
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Connected Accounts</h3>
        
        <div class="space-y-4">
            @foreach($accounts as $account)
                @php
                    $isHealthy = $account->isActive();
                    $isExpired = $account->isTokenExpired();
                @endphp
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border border-[var(--border)] hover:border-[var(--primary)]/30 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: {{ $platformConfig['color'] }}20;">
                            @if($account->account_data['picture'] ?? $account->account_data['thumbnail'] ?? $account->account_data['profile_picture'] ?? false)
                                <img src="{{ $account->account_data['picture'] ?? $account->account_data['thumbnail'] ?? $account->account_data['profile_picture'] }}" 
                                     alt="{{ $account->account_name }}" class="w-12 h-12 rounded-xl object-cover">
                            @else
                                @include('integrations.partials.platform-icon', ['platform' => $platform, 'class' => 'w-6 h-6', 'color' => $platformConfig['color']])
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-[var(--text-primary)]">{{ $account->account_name }}</p>
                            <div class="flex items-center gap-3 text-xs text-[var(--text-muted)] mt-1">
                                <span>ID: {{ Str::limit($account->platform_account_id, 20) }}</span>
                                <span>•</span>
                                <span class="capitalize">{{ $account->account_data['connection_method'] ?? 'OAuth' }}</span>
                                <span>•</span>
                                @if($isHealthy)
                                    <span class="text-green-500">● Active</span>
                                @elseif($isExpired)
                                    <span class="text-amber-500">● Token Expired</span>
                                @else
                                    <span class="text-red-500">● {{ ucfirst($account->status) }}</span>
                                @endif
                            </div>
                            @if($account->last_sync_at)
                                <p class="text-xs text-[var(--text-muted)] mt-1">
                                    Last synced: {{ $account->last_sync_at->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 sm:ml-auto">
                        @if($isExpired || $account->status === 'expired')
                            <a href="{{ route('integrations.reconnect', $account) }}" 
                               class="btn text-sm bg-amber-500 hover:bg-amber-600 text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Reconnect
                            </a>
                        @else
                            <button @click="testConnection({{ $account->id }})" 
                                    class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" 
                                    title="Test Connection">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                            
                            <form action="{{ route('integrations.sync', $account) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" title="Sync Now">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </form>
                            
                            <a href="{{ route('analytics.' . ($platform === 'google_analytics' ? 'google' : $platform)) }}" 
                               class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" 
                               title="View Analytics">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </a>
                        @endif
                        
                        <form action="{{ route('integrations.disconnect', $account) }}" method="POST" class="inline"
                              onsubmit="return confirm('Are you sure you want to disconnect {{ $account->account_name }}? This will remove all associated data.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors" title="Disconnect">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Connect New Account -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">Connect New Account</h3>
        <p class="text-sm text-[var(--text-secondary)] mb-6">Choose a connection method to add a new {{ $platformConfig['name'] }} account.</p>
        
        <!-- Connection Method Tabs -->
        <div class="border-b border-[var(--border)] mb-6">
            <nav class="flex gap-4" role="tablist">
                @foreach($platformConfig['connection_methods'] as $method)
                    <button @click="connectionMethod = '{{ $method }}'"
                            :class="connectionMethod === '{{ $method }}' ? 'border-[var(--primary)] text-[var(--primary)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text-primary)]'"
                            class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors capitalize">
                        @if($method === 'oauth')
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                OAuth (Recommended)
                            </span>
                        @elseif($method === 'page_token' || $method === 'access_token')
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                Access Token
                            </span>
                        @elseif($method === 'api_key')
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                API Key
                            </span>
                        @elseif($method === 'service_account')
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Service Account
                            </span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- OAuth Method -->
        <div x-show="connectionMethod === 'oauth'" x-cloak>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center"
                     style="background-color: {{ $platformConfig['color'] }}20;">
                    @include('integrations.partials.platform-icon', ['platform' => $platform, 'class' => 'w-8 h-8', 'color' => $platformConfig['color']])
                </div>
                <h4 class="text-lg font-medium text-[var(--text-primary)] mb-2">Connect with {{ $platformConfig['name'] }}</h4>
                <p class="text-sm text-[var(--text-secondary)] mb-6 max-w-md mx-auto">
                    Click the button below to authorize access to your {{ $platformConfig['name'] }} account. You'll be redirected to {{ $platformConfig['name'] }} to complete the connection.
                </p>
                
                <a href="{{ route('integrations.' . str_replace('_', '-', $platform) . '.connect') }}" 
                   class="btn text-white inline-flex items-center gap-2"
                   style="background-color: {{ $platformConfig['color'] }};">
                    @include('integrations.partials.platform-icon', ['platform' => $platform, 'class' => 'w-5 h-5'])
                    Connect {{ $platformConfig['name'] }}
                </a>
                
                <p class="text-xs text-[var(--text-muted)] mt-4">
                    Required permissions: {{ implode(', ', $platformConfig['scopes']) }}
                </p>
            </div>
        </div>

        <!-- Access Token Method -->
        <div x-show="connectionMethod === 'page_token' || connectionMethod === 'access_token'" x-cloak>
            @if($platform === 'facebook')
                <!-- Facebook-specific token form -->
                <div class="grid lg:grid-cols-2 gap-6">
                    <form action="{{ route('integrations.connect.token', $platform) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20 mb-4">
                                <h4 class="font-medium text-blue-400 flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Facebook Page Access Token
                                </h4>
                                <p class="text-sm text-blue-400/80">
                                    Use a <strong>Page Access Token</strong> to connect your Facebook Page. 
                                    The token will be validated automatically.
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                    Access Token <span class="text-red-500">*</span>
                                </label>
                                <textarea name="access_token" required rows="4"
                                          class="input font-mono text-xs" 
                                          placeholder="EAABw...your_access_token...">{{ old('access_token') }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                    Page ID <span class="text-[var(--text-muted)]">(optional)</span>
                                </label>
                                <input type="text" name="page_id" 
                                       class="input font-mono" 
                                       placeholder="123456789012345"
                                       value="{{ old('page_id') }}">
                                <p class="text-xs text-[var(--text-muted)] mt-1">
                                    Only needed if your token has access to multiple pages.
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                    Account Label <span class="text-[var(--text-muted)]">(optional)</span>
                                </label>
                                <input type="text" name="account_name" 
                                       class="input" 
                                       placeholder="Auto-detected from token"
                                       value="{{ old('account_name') }}">
                                <p class="text-xs text-[var(--text-muted)] mt-1">
                                    Leave empty to use the Page name from Facebook.
                                </p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Verify & Connect
                            </button>
                        </div>
                    </form>

                    <!-- Help Section -->
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-[var(--surface-hover)] border border-[var(--border)]">
                            <h4 class="font-medium text-[var(--text-primary)] mb-3">How to get a Page Access Token</h4>
                            <ol class="space-y-3 text-sm text-[var(--text-secondary)]">
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] text-xs font-bold flex items-center justify-center">1</span>
                                    <span>Go to <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="text-[var(--primary)] hover:underline">Graph API Explorer</a></span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] text-xs font-bold flex items-center justify-center">2</span>
                                    <span>Select your App (or create one at <a href="https://developers.facebook.com/apps/" target="_blank" class="text-[var(--primary)] hover:underline">Meta Developer Portal</a>)</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] text-xs font-bold flex items-center justify-center">3</span>
                                    <span>Click "Get Token" → "Get Page Access Token"</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] text-xs font-bold flex items-center justify-center">4</span>
                                    <span>Select your Page and grant permissions</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] text-xs font-bold flex items-center justify-center">5</span>
                                    <span>Copy the generated token and paste it here</span>
                                </li>
                            </ol>
                        </div>

                        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
                            <h4 class="font-medium text-amber-500 flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Token Expiration
                            </h4>
                            <p class="text-sm text-amber-500/80">
                                Short-lived tokens expire in ~1 hour. For best results, use a <strong>Long-Lived Page Access Token</strong> 
                                (valid for ~60 days) or exchange it using the Access Token Debugger.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-[var(--surface-hover)] border border-[var(--border)]">
                            <h4 class="font-medium text-[var(--text-primary)] mb-2">Required Permissions</h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs rounded bg-[var(--surface-alt)] text-[var(--text-secondary)]">pages_show_list</span>
                                <span class="px-2 py-1 text-xs rounded bg-[var(--surface-alt)] text-[var(--text-secondary)]">pages_read_engagement</span>
                                <span class="px-2 py-1 text-xs rounded bg-[var(--surface-alt)] text-[var(--text-secondary)]">pages_read_user_content</span>
                                <span class="px-2 py-1 text-xs rounded bg-[var(--surface-alt)] text-[var(--text-secondary)]">read_insights</span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Generic token form for other platforms -->
                <form action="{{ route('integrations.connect.token', $platform) }}" method="POST" class="max-w-lg">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Account Name</label>
                            <input type="text" name="account_name" required 
                                   class="input" placeholder="My {{ $platformConfig['name'] }} Account">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Access Token</label>
                            <textarea name="access_token" required rows="3"
                                      class="input font-mono text-sm" placeholder="Paste your access token here..."></textarea>
                            <p class="text-xs text-[var(--text-muted)] mt-1">
                                You can generate an access token from the {{ $platformConfig['name'] }} developer console.
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Connect Account
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- API Key Method -->
        <div x-show="connectionMethod === 'api_key'" x-cloak>
            <form action="{{ route('integrations.connect.api-key', $platform) }}" method="POST" class="max-w-lg">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Account Name</label>
                        <input type="text" name="account_name" required 
                               class="input" placeholder="My {{ $platformConfig['name'] }} Account">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">API Key</label>
                        <input type="text" name="api_key" required 
                               class="input font-mono" placeholder="Enter your API key">
                    </div>
                    
                    @if($platform === 'youtube')
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Channel ID</label>
                        <input type="text" name="channel_id" required 
                               class="input font-mono" placeholder="UC...">
                        <p class="text-xs text-[var(--text-muted)] mt-1">
                            Find this in your channel's URL or YouTube Studio settings.
                        </p>
                    </div>
                    @endif
                    
                    @if($platform === 'google_analytics')
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Property ID</label>
                        <input type="text" name="property_id" required 
                               class="input font-mono" placeholder="123456789">
                    </div>
                    @endif
                    
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Connect with API Key
                    </button>
                </div>
            </form>
        </div>

        <!-- Service Account Method -->
        <div x-show="connectionMethod === 'service_account'" x-cloak>
            <form action="{{ route('integrations.connect.service-account', $platform) }}" method="POST" enctype="multipart/form-data" class="max-w-lg">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Account Name</label>
                        <input type="text" name="account_name" required 
                               class="input" placeholder="My {{ $platformConfig['name'] }} Property">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Property ID</label>
                        <input type="text" name="property_id" required 
                               class="input font-mono" placeholder="123456789">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">Service Account JSON</label>
                        <div class="border-2 border-dashed border-[var(--border)] rounded-xl p-6 text-center hover:border-[var(--primary)] transition-colors">
                            <input type="file" name="service_account_json" accept=".json" required 
                                   class="hidden" id="service_account_file" @change="handleFileSelect">
                            <label for="service_account_file" class="cursor-pointer">
                                <svg class="w-10 h-10 mx-auto text-[var(--text-muted)] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <span class="text-sm text-[var(--text-secondary)]">
                                    Drop your service account JSON file here or <span class="text-[var(--primary)]">browse</span>
                                </span>
                            </label>
                            <p x-show="selectedFile" x-text="selectedFile" class="text-sm text-green-500 mt-2"></p>
                        </div>
                        <p class="text-xs text-[var(--text-muted)] mt-1">
                            Download this from the Google Cloud Console under Service Accounts.
                        </p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Connect with Service Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sync History -->
    @if($syncLogs->count() > 0)
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Sync History</h3>
        
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Records</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($syncLogs as $log)
                        <tr>
                            <td>{{ $log->socialAccount->account_name ?? 'N/A' }}</td>
                            <td><span class="badge badge-info text-xs">{{ ucfirst($log->sync_type) }}</span></td>
                            <td class="font-mono">{{ number_format($log->records_synced) }}</td>
                            <td>
                                <span class="badge 
                                    @if($log->status === 'completed') badge-success
                                    @elseif($log->status === 'failed') badge-danger
                                    @elseif($log->status === 'running') badge-info
                                    @else badge-warning
                                    @endif text-xs">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="text-[var(--text-muted)]">{{ $log->duration_seconds ? $log->duration_seconds . 's' : '-' }}</td>
                            <td class="text-[var(--text-muted)]">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function platformIntegration() {
    return {
        connectionMethod: '{{ $platformConfig['connection_methods'][0] ?? 'oauth' }}',
        selectedFile: null,
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file.name;
            }
        },
        
        async testConnection(accountId) {
            try {
                const response = await fetch(`/integrations/${accountId}/test`);
                const data = await response.json();
                
                if (data.status === 'healthy') {
                    alert('✓ Connection is healthy!');
                } else {
                    alert('⚠ Connection issue: ' + data.message);
                }
            } catch (error) {
                alert('Failed to test connection');
            }
        }
    };
}
</script>
@endpush
@endsection

