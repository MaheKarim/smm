@extends('layouts.app')

@section('title', 'Integrations')
@section('breadcrumb', 'Connected Accounts')

@section('content')
<div class="space-y-6" x-data="integrations()">
    <!-- Page Header with Instant Refresh -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Connected Accounts</h1>
            <p class="text-[var(--text-secondary)] mt-1">Manage your social media platform connections and sync settings</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Instant Refresh Button -->
            <button 
                @click="instantRefresh()"
                :disabled="refreshing"
                class="btn btn-primary relative overflow-hidden"
                :class="{ 'opacity-75 cursor-not-allowed': refreshing }"
            >
                <svg x-show="!refreshing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg x-show="refreshing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="refreshing ? 'Syncing...' : 'Instant Refresh'"></span>
                <div x-show="refreshProgress > 0 && refreshProgress < 100" 
                     class="absolute bottom-0 left-0 h-1 bg-white/30 transition-all duration-300"
                     :style="{ width: refreshProgress + '%' }"></div>
            </button>
            
            <form action="{{ route('integrations.sync-all') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sync All
                </button>
            </form>
        </div>
    </div>

    <!-- Platform Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($platforms as $key => $platform)
        <div class="card p-6 group hover:shadow-lg transition-all duration-300 cursor-pointer"
             @click="window.location='{{ route('integrations.show', $key) }}'">
            <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110"
                     style="background-color: {{ $platform['color'] }}20;">
                    @include('integrations.partials.platform-icon', ['platform' => $key, 'class' => 'w-7 h-7', 'color' => $platform['color']])
                </div>
                <div class="flex items-center gap-1">
                    @php
                        $health = $platformHealth[$key] ?? ['status' => 'none', 'total' => 0];
                    @endphp
                    @if($health['status'] === 'healthy')
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    @elseif($health['status'] === 'warning')
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    @endif
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-1">{{ $platform['name'] }}</h3>
            <p class="text-sm text-[var(--text-secondary)] mb-4">
                {{ $accounts->get($key, collect())->count() }} {{ Str::plural('account', $accounts->get($key, collect())->count()) }} connected
            </p>
            
            <div class="flex items-center justify-between">
                <a href="{{ route('integrations.show', $key) }}" 
                   class="text-sm font-medium hover:underline"
                   style="color: {{ $platform['color'] }};"
                   @click.stop>
                    Manage →
                </a>
                @if(isset($lastSyncTimes[$key]))
                    <span class="text-xs text-[var(--text-muted)]" title="Last synced">
                        {{ \Carbon\Carbon::parse($lastSyncTimes[$key])->diffForHumans() }}
                    </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Connection Status Overview -->
    @if($accounts->flatten()->count() > 0)
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-[var(--text-primary)]">All Connected Accounts</h3>
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    Active
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    Needs Attention
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    Error
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($accounts->flatten() as $account)
                @php
                    $platform = $platforms[$account->platform] ?? null;
                    $isHealthy = $account->isActive();
                    $isExpired = $account->isTokenExpired();
                @endphp
                <div class="flex items-center justify-between p-4 rounded-xl border border-[var(--border)] bg-[var(--surface-hover)] hover:bg-[var(--surface-alt)] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: {{ $platform['color'] ?? '#666' }}20;">
                            @include('integrations.partials.platform-icon', ['platform' => $account->platform, 'class' => 'w-6 h-6', 'color' => $platform['color'] ?? '#666'])
                        </div>
                        <div>
                            <p class="font-medium text-[var(--text-primary)]">{{ $account->account_name }}</p>
                            <div class="flex items-center gap-2 text-xs text-[var(--text-muted)]">
                                <span class="capitalize">{{ str_replace('_', ' ', $account->platform) }}</span>
                                <span>•</span>
                                @if($isHealthy)
                                    <span class="text-green-500 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </span>
                                @elseif($isExpired)
                                    <span class="text-amber-500 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Token Expired
                                    </span>
                                @else
                                    <span class="text-red-500 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        {{ ucfirst($account->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        @if($isExpired || $account->status === 'expired')
                            <a href="{{ route('integrations.reconnect', $account) }}" 
                               class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-500/10 text-amber-500 hover:bg-amber-500/20 transition-colors">
                                Reconnect
                            </a>
                        @else
                            <form action="{{ route('integrations.sync', $account) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--primary)]/10 rounded-lg transition-colors" title="Sync">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('analytics.' . ($account->platform === 'google_analytics' ? 'google' : $account->platform)) }}" 
                           class="p-2 text-[var(--text-secondary)] hover:text-[var(--primary)] hover:bg-[var(--primary)]/10 rounded-lg transition-colors" title="View Analytics">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </a>
                        
                        <form action="{{ route('integrations.disconnect', $account) }}" method="POST" class="inline" 
                              onsubmit="return confirm('Are you sure you want to disconnect {{ $account->account_name }}?')">
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
    @else
    <!-- Empty State -->
    <div class="card p-12 text-center">
        <div class="w-20 h-20 rounded-2xl bg-[var(--primary)]/10 flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-[var(--primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-[var(--text-primary)] mb-2">No accounts connected yet</h3>
        <p class="text-[var(--text-secondary)] mb-6 max-w-md mx-auto">
            Connect your social media accounts to start tracking analytics and performance across all platforms.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach(['facebook', 'youtube', 'instagram', 'google_analytics'] as $platform)
                <a href="{{ route('integrations.show', $platform) }}" 
                   class="btn btn-secondary text-sm">
                    Connect {{ ucfirst(str_replace('_', ' ', $platform)) }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Sync Activity -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-[var(--text-primary)]">Recent Sync Activity</h3>
            <span class="text-sm text-[var(--text-muted)]">Last 10 syncs</span>
        </div>
        
        @if($syncLogs->isEmpty())
            <div class="text-center py-8 text-[var(--text-muted)]">
                <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <p>No sync activity yet. Connect an account to get started.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Platform</th>
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
                            <tr class="hover:bg-[var(--surface-hover)]">
                                <td>
                                    <div class="flex items-center gap-2">
                                        @php $platform = $platforms[$log->platform] ?? null; @endphp
                                        <div class="w-6 h-6 rounded flex items-center justify-center"
                                             style="background-color: {{ $platform['color'] ?? '#666' }}20;">
                                            @include('integrations.partials.platform-icon', ['platform' => $log->platform, 'class' => 'w-3.5 h-3.5', 'color' => $platform['color'] ?? '#666'])
                                        </div>
                                        <span class="capitalize">{{ str_replace('_', ' ', $log->platform) }}</span>
                                    </div>
                                </td>
                                <td>{{ $log->socialAccount->account_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-info text-xs">{{ ucfirst($log->sync_type) }}</span>
                                </td>
                                <td class="font-mono text-sm">{{ number_format($log->records_synced) }}</td>
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
                                <td class="text-sm text-[var(--text-muted)]">
                                    {{ $log->duration_seconds ? $log->duration_seconds . 's' : '-' }}
                                </td>
                                <td class="text-sm text-[var(--text-muted)]">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function integrations() {
    return {
        refreshing: false,
        refreshProgress: 0,
        jobId: null,
        
        async instantRefresh() {
            if (this.refreshing) return;
            
            this.refreshing = true;
            this.refreshProgress = 0;
            
            try {
                const response = await fetch('{{ route('integrations.instant-refresh') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                
                if (response.status === 429) {
                    const data = await response.json();
                    alert(`Please wait ${data.retry_after} seconds before refreshing again.`);
                    this.refreshing = false;
                    return;
                }
                
                const data = await response.json();
                this.jobId = data.job_id;
                
                // Poll for status
                await this.pollSyncStatus();
                
            } catch (error) {
                console.error('Refresh failed:', error);
                this.refreshing = false;
            }
        },
        
        async pollSyncStatus() {
            const poll = async () => {
                try {
                    const response = await fetch(`/integrations/sync-status/${this.jobId}`);
                    const data = await response.json();
                    
                    this.refreshProgress = data.progress || 0;
                    
                    if (data.status === 'completed' || data.status === 'failed') {
                        this.refreshing = false;
                        this.refreshProgress = 100;
                        
                        // Reload to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Continue polling
                        setTimeout(poll, 1000);
                    }
                } catch (error) {
                    console.error('Status poll failed:', error);
                    this.refreshing = false;
                }
            };
            
            await poll();
        }
    };
}
</script>
@endpush
@endsection
