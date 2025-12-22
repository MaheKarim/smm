@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Overview')

@section('content')
<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Welcome back, {{ auth()->user()->name }}!</h1>
            <p class="text-[var(--text-secondary)] mt-1">Here's what's happening with your social media accounts.</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="input py-2 text-sm w-40" id="dateRange">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>
    </div>

    <!-- Platform Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Facebook Card -->
        <div class="stat-card group animate-fade-in stagger-1" style="opacity: 0;">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-[#1877f2]/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-[#1877f2]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <span class="badge {{ $connectedAccounts['facebook']['count'] > 0 ? 'badge-success' : 'badge-warning' }}">
                    {{ $connectedAccounts['facebook']['count'] }} connected
                </span>
            </div>
            <p class="stat-label">Facebook Views</p>
            <p class="stat-value">{{ number_format($platformStats['facebook']['views']) }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="stat-change {{ $platformStats['facebook']['change'] >= 0 ? 'positive' : 'negative' }}">
                    @if($platformStats['facebook']['change'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    @endif
                    {{ abs($platformStats['facebook']['change']) }}%
                </span>
                <span class="text-xs text-[var(--text-muted)]">vs last period</span>
            </div>
        </div>

        <!-- YouTube Card -->
        <div class="stat-card group animate-fade-in stagger-2" style="opacity: 0;">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-[#ff0000]/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-[#ff0000]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <span class="badge {{ $connectedAccounts['youtube']['count'] > 0 ? 'badge-success' : 'badge-warning' }}">
                    {{ $connectedAccounts['youtube']['count'] }} connected
                </span>
            </div>
            <p class="stat-label">YouTube Views</p>
            <p class="stat-value">{{ number_format($platformStats['youtube']['views']) }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="stat-change {{ $platformStats['youtube']['change'] >= 0 ? 'positive' : 'negative' }}">
                    @if($platformStats['youtube']['change'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    @endif
                    {{ abs($platformStats['youtube']['change']) }}%
                </span>
                <span class="text-xs text-[var(--text-muted)]">vs last period</span>
            </div>
        </div>

        <!-- Instagram Card -->
        <div class="stat-card group animate-fade-in stagger-3" style="opacity: 0;">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-[#e4405f]/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-[#e4405f]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </div>
                <span class="badge {{ $connectedAccounts['instagram']['count'] > 0 ? 'badge-success' : 'badge-warning' }}">
                    {{ $connectedAccounts['instagram']['count'] }} connected
                </span>
            </div>
            <p class="stat-label">Instagram Reach</p>
            <p class="stat-value">{{ number_format($platformStats['instagram']['reach']) }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="stat-change {{ $platformStats['instagram']['change'] >= 0 ? 'positive' : 'negative' }}">
                    @if($platformStats['instagram']['change'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    @endif
                    {{ abs($platformStats['instagram']['change']) }}%
                </span>
                <span class="text-xs text-[var(--text-muted)]">vs last period</span>
            </div>
        </div>

        <!-- Google Analytics Card -->
        <div class="stat-card group animate-fade-in stagger-4" style="opacity: 0;">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-[#4285f4]/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-[#4285f4]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                </div>
                <span class="badge {{ $connectedAccounts['google_analytics']['count'] > 0 ? 'badge-success' : 'badge-warning' }}">
                    {{ $connectedAccounts['google_analytics']['count'] }} connected
                </span>
            </div>
            <p class="stat-label">Website Sessions</p>
            <p class="stat-value">{{ number_format($platformStats['google_analytics']['sessions']) }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="stat-change {{ $platformStats['google_analytics']['change'] >= 0 ? 'positive' : 'negative' }}">
                    @if($platformStats['google_analytics']['change'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    @endif
                    {{ abs($platformStats['google_analytics']['change']) }}%
                </span>
                <span class="text-xs text-[var(--text-muted)]">vs last period</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Views Over Time Chart -->
        <div class="card p-6 animate-fade-in stagger-5" style="opacity: 0;">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Views Over Time</h3>
                    <p class="text-sm text-[var(--text-secondary)]">Daily views across all platforms</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#1877f2]"></span>
                    <span class="text-xs text-[var(--text-muted)]">Facebook</span>
                    <span class="w-3 h-3 rounded-full bg-[#ff0000] ml-2"></span>
                    <span class="text-xs text-[var(--text-muted)]">YouTube</span>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="viewsChart"></canvas>
            </div>
        </div>

        <!-- Engagement Chart -->
        <div class="card p-6 animate-fade-in stagger-5" style="opacity: 0;">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Engagement Breakdown</h3>
                    <p class="text-sm text-[var(--text-secondary)]">Total engagement by type</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="engagementChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activity -->
        <div class="card p-6 lg:col-span-2 animate-fade-in" style="opacity: 0; animation-delay: 0.6s;">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-[var(--text-primary)]">Recent Sync Activity</h3>
                <a href="{{ route('integrations.index') }}" class="text-sm text-[var(--primary)] hover:underline">View all</a>
            </div>
            
            @if($recentSyncs->isEmpty())
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-[var(--text-muted)] mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <p class="text-[var(--text-secondary)]">No sync activity yet</p>
                    <p class="text-sm text-[var(--text-muted)]">Connect your accounts to start syncing data</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentSyncs as $sync)
                        <div class="flex items-center gap-4 p-3 rounded-lg bg-[var(--surface-hover)]">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center 
                                @if($sync->platform === 'facebook') bg-[#1877f2]/10
                                @elseif($sync->platform === 'youtube') bg-[#ff0000]/10
                                @elseif($sync->platform === 'instagram') bg-[#e4405f]/10
                                @else bg-[#4285f4]/10
                                @endif">
                                <!-- Platform icon -->
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[var(--text-primary)] truncate">
                                    {{ ucfirst($sync->platform) }} Sync
                                </p>
                                <p class="text-xs text-[var(--text-muted)]">
                                    {{ $sync->records_synced }} records • {{ $sync->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="badge 
                                @if($sync->status === 'completed') badge-success
                                @elseif($sync->status === 'failed') badge-danger
                                @elseif($sync->status === 'running') badge-info
                                @else badge-warning
                                @endif">
                                {{ ucfirst($sync->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="card p-6 animate-fade-in" style="opacity: 0; animation-delay: 0.7s;">
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('integrations.index') }}" class="flex items-center gap-3 p-3 rounded-lg bg-[var(--surface-hover)] hover:bg-[var(--surface-alt)] transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-[var(--primary)]/10 flex items-center justify-center group-hover:bg-[var(--primary)]/20 transition-colors">
                        <svg class="w-5 h-5 text-[var(--primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[var(--text-primary)]">Connect Account</p>
                        <p class="text-xs text-[var(--text-muted)]">Add a new social account</p>
                    </div>
                </a>

                <button class="w-full flex items-center gap-3 p-3 rounded-lg bg-[var(--surface-hover)] hover:bg-[var(--surface-alt)] transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center group-hover:bg-green-500/20 transition-colors">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-medium text-[var(--text-primary)]">Sync All Data</p>
                        <p class="text-xs text-[var(--text-muted)]">Refresh all analytics</p>
                    </div>
                </button>

                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 p-3 rounded-lg bg-[var(--surface-hover)] hover:bg-[var(--surface-alt)] transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center group-hover:bg-amber-500/20 transition-colors">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[var(--text-primary)]">Export Report</p>
                        <p class="text-xs text-[var(--text-muted)]">Download analytics PDF</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Views Over Time Chart
    const viewsCtx = document.getElementById('viewsChart');
    if (viewsCtx) {
        new Chart(viewsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [
                    {
                        label: 'Facebook',
                        data: {!! json_encode($chartData['datasets']['facebook']) !!},
                        borderColor: '#1877f2',
                        backgroundColor: 'rgba(24, 119, 242, 0.1)',
                        fill: true,
                    },
                    {
                        label: 'YouTube',
                        data: {!! json_encode($chartData['datasets']['youtube']) !!},
                        borderColor: '#ff0000',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)',
                        fill: true,
                    },
                    {
                        label: 'Instagram',
                        data: {!! json_encode($chartData['datasets']['instagram']) !!},
                        borderColor: '#e4405f',
                        backgroundColor: 'rgba(228, 64, 95, 0.1)',
                        fill: true,
                    },
                    {
                        label: 'Google Analytics',
                        data: {!! json_encode($chartData['datasets']['google_analytics']) !!},
                        borderColor: '#4285f4',
                        backgroundColor: 'rgba(66, 133, 244, 0.1)',
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxTicksLimit: 7,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
    }

    // Engagement Doughnut Chart
    const engagementCtx = document.getElementById('engagementChart');
    if (engagementCtx) {
        new Chart(engagementCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Likes', 'Comments', 'Shares', 'Saves'],
                datasets: [{
                    data: [
                        {{ $platformStats['facebook']['engagement'] + $platformStats['instagram']['engagement'] }},
                        {{ intval(($platformStats['facebook']['engagement'] + $platformStats['instagram']['engagement']) * 0.3) }},
                        {{ intval(($platformStats['facebook']['engagement'] + $platformStats['instagram']['engagement']) * 0.2) }},
                        {{ intval(($platformStats['facebook']['engagement'] + $platformStats['instagram']['engagement']) * 0.1) }}
                    ],
                    backgroundColor: [
                        '#ef4444',
                        '#3b82f6',
                        '#22c55e',
                        '#f59e0b'
                    ],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 16,
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection

