@extends('layouts.app')

@section('title', 'Instagram Analytics')
@section('breadcrumb-parent', 'Analytics')
@section('breadcrumb', 'Instagram')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Instagram Analytics</h1>
            <p class="text-[var(--text-secondary)] mt-1">Track your Instagram account performance</p>
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
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#833ab4]/10 via-[#fd1d1d]/10 to-[#fcb045]/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#e4405f]" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">No Instagram Accounts Connected</h3>
            <p class="text-[var(--text-secondary)] mb-6">Connect your Instagram Business account to start tracking analytics.</p>
            <a href="{{ route('integrations.instagram.connect') }}" class="btn btn-primary">
                Connect Instagram Account
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
                <p class="stat-label">Profile Views</p>
                <p class="stat-value">{{ number_format($analyticsData['total_profile_views'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Followers Gained</p>
                <p class="stat-value text-green-500">+{{ number_format($analyticsData['followers_gained'] ?? 0) }}</p>
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

        <!-- Account Info & Posts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @if($accountData)
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Account Overview</h3>
                <div class="flex items-center gap-4 mb-6">
                    @if($accountData->profile_picture_url)
                        <img src="{{ $accountData->profile_picture_url }}" alt="{{ $accountData->username }}" class="w-16 h-16 rounded-full">
                    @endif
                    <div>
                        <h4 class="font-semibold text-[var(--text-primary)]">{{ $accountData->name ?? $accountData->username }}</h4>
                        <p class="text-sm text-[var(--text-muted)]">@{{ $accountData->username }}</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Followers</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($accountData->followers_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Following</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($accountData->follows_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[var(--text-secondary)]">Posts</span>
                        <span class="font-semibold text-[var(--text-primary)]">{{ number_format($accountData->media_count) }}</span>
                    </div>
                </div>
            </div>
            @endif

            <div class="card p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Recent Posts</h3>
                @if($posts->isEmpty())
                    <p class="text-[var(--text-muted)] text-center py-8">No posts synced yet.</p>
                @else
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        @foreach($posts as $post)
                            <div class="aspect-square rounded-lg overflow-hidden relative group">
                                @if($post->media_url || $post->thumbnail_url)
                                    <img src="{{ $post->thumbnail_url ?? $post->media_url }}" alt="" class="w-full h-full object-cover">
                                @endif
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="badge badge-info">{{ $post->media_type }}</span>
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
                        borderColor: '#e4405f',
                        backgroundColor: 'rgba(228, 64, 95, 0.1)',
                        fill: true,
                    },
                    {
                        label: 'Impressions',
                        data: {!! json_encode($chartData['impressions']) !!},
                        borderColor: '#833ab4',
                        backgroundColor: 'rgba(131, 58, 180, 0.1)',
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
                    backgroundColor: '#e4405f',
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

