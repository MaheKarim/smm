@extends('layouts.app')

@section('title', 'Google Analytics')
@section('breadcrumb-parent', 'Analytics')
@section('breadcrumb', 'Google Analytics')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Google Analytics</h1>
            <p class="text-[var(--text-secondary)] mt-1">Track your website traffic and performance</p>
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
            <div class="w-16 h-16 rounded-full bg-[#4285f4]/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#4285f4]" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">No Google Analytics Properties Connected</h3>
            <p class="text-[var(--text-secondary)] mb-6">Connect your Google Analytics property to start tracking website analytics.</p>
            <a href="{{ route('integrations.google-analytics.connect') }}" class="btn btn-primary">
                Connect Google Analytics
            </a>
        </div>
    @else
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <p class="stat-label">Sessions</p>
                <p class="stat-value">{{ number_format($analyticsData['total_sessions'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Users</p>
                <p class="stat-value">{{ number_format($analyticsData['total_users'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Pageviews</p>
                <p class="stat-value">{{ number_format($analyticsData['total_pageviews'] ?? 0) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Bounce Rate</p>
                <p class="stat-value">{{ number_format($analyticsData['avg_bounce_rate'] ?? 0, 1) }}%</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Sessions & Users</h3>
                <div class="chart-container">
                    <canvas id="sessionsChart"></canvas>
                </div>
            </div>
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Pageviews</h3>
                <div class="chart-container">
                    <canvas id="pageviewsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Traffic Sources & Top Pages -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Traffic Sources</h3>
                @if($trafficSources->isEmpty())
                    <p class="text-[var(--text-muted)] text-center py-8">No traffic data available yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($trafficSources as $source)
                            <div class="flex items-center justify-between">
                                <span class="text-[var(--text-primary)]">{{ $source->dimension_value }}</span>
                                <span class="font-semibold text-[var(--text-primary)]">{{ number_format($source->total_sessions) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card p-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Top Pages</h3>
                @if($topPages->isEmpty())
                    <p class="text-[var(--text-muted)] text-center py-8">No page data available yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($topPages as $page)
                            <div class="flex items-center justify-between">
                                <span class="text-[var(--text-primary)] truncate flex-1 mr-4">{{ $page->dimension_value }}</span>
                                <span class="font-semibold text-[var(--text-primary)]">{{ number_format($page->total_pageviews) }}</span>
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
    const sessionsCtx = document.getElementById('sessionsChart');
    if (sessionsCtx) {
        new Chart(sessionsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [
                    {
                        label: 'Sessions',
                        data: {!! json_encode($chartData['sessions']) !!},
                        borderColor: '#4285f4',
                        backgroundColor: 'rgba(66, 133, 244, 0.1)',
                        fill: true,
                    },
                    {
                        label: 'Users',
                        data: {!! json_encode($chartData['users']) !!},
                        borderColor: '#34a853',
                        backgroundColor: 'rgba(52, 168, 83, 0.1)',
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

    const pageviewsCtx = document.getElementById('pageviewsChart');
    if (pageviewsCtx) {
        new Chart(pageviewsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [{
                    label: 'Pageviews',
                    data: {!! json_encode($chartData['pageviews']) !!},
                    backgroundColor: '#4285f4',
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

