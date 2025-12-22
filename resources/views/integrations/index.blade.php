@extends('layouts.app')

@section('title', 'Integrations')
@section('breadcrumb-parent', 'Dashboard')
@section('breadcrumb', 'Connected Accounts')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Connected Accounts</h1>
            <p class="text-[var(--text-secondary)] mt-1">Manage your social media platform connections</p>
        </div>
        <form action="{{ route('integrations.sync-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Sync All
            </button>
        </form>
    </div>

    <!-- Platform Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Facebook -->
        <div class="card p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-xl bg-[#1877f2] flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Facebook Pages</h3>
                    <p class="text-sm text-[var(--text-secondary)]">
                        {{ $accounts->get('facebook', collect())->count() }} page(s) connected
                    </p>
                </div>
            </div>

            @if($accounts->get('facebook', collect())->count() > 0)
                <div class="space-y-3 mb-4">
                    @foreach($accounts->get('facebook') as $account)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--surface-hover)]">
                            <div class="flex items-center gap-3">
                                <img src="{{ $account->account_data['picture'] ?? 'https://via.placeholder.com/40' }}" alt="{{ $account->account_name }}" class="w-10 h-10 rounded-full">
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">{{ $account->account_name }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">
                                        @if($account->isActive())
                                            <span class="text-green-500">● Active</span>
                                        @else
                                            <span class="text-amber-500">● Needs reauthorization</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('integrations.sync', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" title="Sync">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form action="{{ route('integrations.disconnect', $account) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this account?')">
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
            @endif

            <a href="{{ route('integrations.facebook.connect') }}" class="btn btn-secondary w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Connect Facebook Page
            </a>
        </div>

        <!-- YouTube -->
        <div class="card p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-xl bg-[#ff0000] flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">YouTube Channels</h3>
                    <p class="text-sm text-[var(--text-secondary)]">
                        {{ $accounts->get('youtube', collect())->count() }} channel(s) connected
                    </p>
                </div>
            </div>

            @if($accounts->get('youtube', collect())->count() > 0)
                <div class="space-y-3 mb-4">
                    @foreach($accounts->get('youtube') as $account)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--surface-hover)]">
                            <div class="flex items-center gap-3">
                                <img src="{{ $account->account_data['thumbnail'] ?? 'https://via.placeholder.com/40' }}" alt="{{ $account->account_name }}" class="w-10 h-10 rounded-full">
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">{{ $account->account_name }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">
                                        @if($account->isActive())
                                            <span class="text-green-500">● Active</span>
                                        @else
                                            <span class="text-amber-500">● Needs reauthorization</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('integrations.sync', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" title="Sync">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form action="{{ route('integrations.disconnect', $account) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this account?')">
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
            @endif

            <a href="{{ route('integrations.youtube.connect') }}" class="btn btn-secondary w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Connect YouTube Channel
            </a>
        </div>

        <!-- Instagram -->
        <div class="card p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#833ab4] via-[#fd1d1d] to-[#fcb045] flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Instagram Business</h3>
                    <p class="text-sm text-[var(--text-secondary)]">
                        {{ $accounts->get('instagram', collect())->count() }} account(s) connected
                    </p>
                </div>
            </div>

            @if($accounts->get('instagram', collect())->count() > 0)
                <div class="space-y-3 mb-4">
                    @foreach($accounts->get('instagram') as $account)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--surface-hover)]">
                            <div class="flex items-center gap-3">
                                <img src="{{ $account->account_data['profile_picture'] ?? 'https://via.placeholder.com/40' }}" alt="{{ $account->account_name }}" class="w-10 h-10 rounded-full">
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">@{{ $account->account_name }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">
                                        @if($account->isActive())
                                            <span class="text-green-500">● Active</span>
                                        @else
                                            <span class="text-amber-500">● Needs reauthorization</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('integrations.sync', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" title="Sync">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form action="{{ route('integrations.disconnect', $account) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this account?')">
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
            @endif

            <a href="{{ route('integrations.instagram.connect') }}" class="btn btn-secondary w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Connect Instagram Account
            </a>
        </div>

        <!-- Google Analytics -->
        <div class="card p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-xl bg-[#f9ab00] flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.84 2.9977v17.004c0 1.6569-1.3431 3-3 3h-3.6211v-7.5028h2.5151l.3768-2.9211h-2.8918v-1.8611c0-.8461.2348-1.4228 1.4492-1.4228l1.5488-.0007V6.6901c-.2679-.0356-1.1875-.1152-2.2578-.1152-2.2339 0-3.7637 1.3636-3.7637 3.8686v2.1524H10.18v2.9211h2.5782V23h-9.758c-1.6569 0-3-1.3431-3-3V3c0-1.6569 1.3431-3 3-3h17.004c.7956 0 1.5587.3161 2.1213.8787.5626.5627.8787 1.3257.8787 2.1213z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Google Analytics</h3>
                    <p class="text-sm text-[var(--text-secondary)]">
                        {{ $accounts->get('google_analytics', collect())->count() }} property(s) connected
                    </p>
                </div>
            </div>

            @if($accounts->get('google_analytics', collect())->count() > 0)
                <div class="space-y-3 mb-4">
                    @foreach($accounts->get('google_analytics') as $account)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--surface-hover)]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#4285f4]/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#4285f4]" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">{{ $account->account_name }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">
                                        @if($account->isActive())
                                            <span class="text-green-500">● Active</span>
                                        @else
                                            <span class="text-amber-500">● Needs reauthorization</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('integrations.sync', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--surface-alt)] rounded-lg transition-colors" title="Sync">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form action="{{ route('integrations.disconnect', $account) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this account?')">
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
            @endif

            <a href="{{ route('integrations.google-analytics.connect') }}" class="btn btn-secondary w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Connect Google Analytics
            </a>
        </div>
    </div>

    <!-- Sync History -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Recent Sync History</h3>
        
        @if($syncLogs->isEmpty())
            <div class="text-center py-8 text-[var(--text-muted)]">
                No sync history available yet.
            </div>
        @else
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Records</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($syncLogs as $log)
                            <tr>
                                <td>
                                    <span class="capitalize">{{ str_replace('_', ' ', $log->platform) }}</span>
                                </td>
                                <td>{{ $log->socialAccount->account_name ?? 'N/A' }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($log->sync_type) }}</span></td>
                                <td>{{ number_format($log->records_synced) }}</td>
                                <td>
                                    <span class="badge 
                                        @if($log->status === 'completed') badge-success
                                        @elseif($log->status === 'failed') badge-danger
                                        @elseif($log->status === 'running') badge-info
                                        @else badge-warning
                                        @endif">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>{{ $log->duration_seconds ? $log->duration_seconds . 's' : '-' }}</td>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

