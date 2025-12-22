@extends('layouts.app')

@section('title', 'Facebook Analytics')
@section('breadcrumb-parent', 'Analytics')
@section('breadcrumb', 'Facebook')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Facebook Analytics</h1>
            <p class="text-[var(--text-secondary)] mt-1">Track your Facebook Page performance</p>
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
        <!-- No accounts connected -->
        <div class="card p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-[#1877f2]/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#1877f2]" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">No Facebook Pages Connected</h3>
            <p class="text-[var(--text-secondary)] mb-6">Connect your Facebook Page to start tracking analytics.</p>
            <a href="{{ route('integrations.facebook.connect') }}" class="btn btn-primary">
                Connect Facebook Page
            </a>
        </div>
    @else
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="stat-label">Total Reach</p>
                <p class="stat-value">{{ number_format($analyticsData['total_reach'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Impressions</p>
                <p class="stat-value">{{ number_format($analyticsData['total_impressions'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Engagement</p>
                <p class="stat-value">{{ number_format($analyticsData['total_engagement'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg. Engagement Rate</p>
                <p class="stat-value">{{ number_format($analyticsData['avg_engagement_rate'] ?? 0, 2) }}%</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Reach & Impressions</h3>
                <div class="chart-container">
                    <canvas id="reachChart"></canvas>
                </div>
            </div>
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Engagement</h3>
                <div class="chart-container">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Page Info & Posts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Page Info -->
            @if($pageData)
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Page Overview</h3>
                <div class="flex items-center gap-4 mb-6">
                    @if($pageData->profile_picture_url)
                        <img src="{{ $pageData->profile_picture_url }}" alt="{{ $pageData->name }}" class="w-16 h-16 rounded-xl">
                    @endif
                    <div>
                        <h4 class="font-semibold text-[var(--text-primary)]">{{ $pageData->name }}</h4>
                        <p class="text-sm text-[var(--text-muted)]">{{ $pageData->category }}</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Followers</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($pageData->followers_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Page Likes</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($pageData->likes_count) }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Top Posts -->
            <div class="card p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Recent Posts</h3>
                @if($posts->isEmpty())
                    <p class="text-[var(--text-muted)] text-center py-8">No posts synced yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($posts->take(5) as $post)
                            <div class="flex items-start gap-4 p-3 rounded-lg bg-[var(--surface-hover)]">
                                @if($post->full_picture)
                                    <img src="{{ $post->full_picture }}" alt="" class="w-16 h-16 object-cover rounded-lg">
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-[var(--text-primary)] line-clamp-2">{{ $post->message ?? $post->story }}</p>
                                    <p class="text-xs text-[var(--text-muted)] mt-1">{{ $post->published_at?->diffForHumans() }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-info">{{ ucfirst($post->post_type) }}</span>
                                </div>
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
    const reachCtx = document.getElementById('reachChart');
    if (reachCtx) {
        new Chart(reachCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [
                    {
                        label: 'Reach',
                        data: {!! json_encode($chartData['reach']) !!},
                        borderColor: '#1877f2',
                        backgroundColor: 'rgba(24, 119, 242, 0.1)',
                        fill: true,
                    },
                    {
                        label: 'Impressions',
                        data: {!! json_encode($chartData['impressions']) !!},
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    const engagementCtx = document.getElementById('engagementChart');
    if (engagementCtx) {
        new Chart(engagementCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [{
                    label: 'Engagement',
                    data: {!! json_encode($chartData['engagement']) !!},
                    backgroundColor: '#1877f2',
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

