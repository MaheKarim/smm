@extends('layouts.app')

@section('title', 'YouTube Analytics')
@section('breadcrumb-parent', 'Analytics')
@section('breadcrumb', 'YouTube')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">YouTube Analytics</h1>
            <p class="text-[var(--text-secondary)] mt-1">Track your YouTube channel performance</p>
        </div>
        <div class="flex items-center gap-3">
            @if($accounts->count() > 1)
                <select class="input py-2 text-sm" onchange="window.location.href='?account='+this.value+'&days={{ $days }}'">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ $selectedAccount && $selectedAccount->id === $account->id ? 'selected' : '' }}>
                            {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            @endif
            <select class="input py-2 text-sm" onchange="window.location.href='?account={{ $selectedAccount?->id }}&days='+this.value">
                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </div>
    </div>

    @if($accounts->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-[#ff0000]/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#ff0000]" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">No YouTube Channels Connected</h3>
            <p class="text-[var(--text-secondary)] mb-6">Connect your YouTube channel to start tracking analytics.</p>
            <a href="{{ route('integrations.youtube.connect') }}" class="btn btn-primary">
                Connect YouTube Channel
            </a>
        </div>
    @else
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="stat-label">Total Views</p>
                <p class="stat-value">{{ number_format($analyticsData['total_views'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Watch Time (hrs)</p>
                <p class="stat-value">{{ number_format($analyticsData['total_watch_time'] ?? 0, 1) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Subscribers Gained</p>
                <p class="stat-value text-green-500">+{{ number_format($analyticsData['total_subscribers_gained'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg. CTR</p>
                <p class="stat-value">{{ number_format($analyticsData['avg_ctr'] ?? 0, 2) }}%</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Views & Watch Time</h3>
                <div class="chart-container">
                    <canvas id="viewsChart"></canvas>
                </div>
            </div>
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Subscriber Growth</h3>
                <div class="chart-container">
                    <canvas id="subscribersChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Channel Info & Videos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @if($channelData)
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Channel Overview</h3>
                <div class="flex items-center gap-4 mb-6">
                    @if($channelData->thumbnail_url)
                        <img src="{{ $channelData->thumbnail_url }}" alt="{{ $channelData->title }}" class="w-16 h-16 rounded-full">
                    @endif
                    <div>
                        <h4 class="font-semibold text-[var(--text-primary)]">{{ $channelData->title }}</h4>
                        <p class="text-sm text-[var(--text-muted)]">{{ $channelData->custom_url }}</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Subscribers</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($channelData->subscriber_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Total Videos</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($channelData->video_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Total Views</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($channelData->view_count) }}</span>
                    </div>
                </div>
            </div>
            @endif

            <div class="card p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Recent Videos</h3>
                @if($videos->isEmpty())
                    <p class="text-[var(--text-muted)] text-center py-8">No videos synced yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($videos->take(5) as $video)
                            <div class="flex items-start gap-4 p-3 rounded-lg bg-[var(--surface-hover)]">
                                @if($video->thumbnail_url)
                                    <img src="{{ $video->thumbnail_url }}" alt="" class="w-24 h-14 object-cover rounded-lg">
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-[var(--text-primary)] line-clamp-2">{{ $video->title }}</p>
                                    <p class="text-xs text-[var(--text-muted)] mt-1">
                                        {{ $video->getFormattedDuration() }} • {{ $video->published_at?->diffForHumans() }}
                                    </p>
                                </div>
                                @if($video->is_short)
                                    <span class="badge badge-info">Short</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@if(!$accounts->isEmpty())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewsCtx = document.getElementById('viewsChart');
    if (viewsCtx) {
        new Chart(viewsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [
                    {
                        label: 'Views',
                        data: {!! json_encode($chartData['views']) !!},
                        borderColor: '#ff0000',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)',
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Watch Time (hrs)',
                        data: {!! json_encode($chartData['watchTime']) !!},
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', display: true, position: 'left', beginAtZero: true },
                    y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    const subscribersCtx = document.getElementById('subscribersChart');
    if (subscribersCtx) {
        new Chart(subscribersCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [{
                    label: 'Net Subscribers',
                    data: {!! json_encode($chartData['subscribers']) !!},
                    backgroundColor: function(context) {
                        return context.raw >= 0 ? '#22c55e' : '#ef4444';
                    },
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>
@endpush
@endif
@endsection

