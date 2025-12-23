@extends('layouts.app')

@section('title', 'Settings')
@section('breadcrumb', 'Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Settings</h1>
        <p class="text-[var(--text-secondary)] mt-1">Manage your account and preferences</p>
    </div>

    <!-- Profile Settings -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Profile Information</h3>
        <form action="{{ route('settings.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>

    <!-- Preferences -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Preferences</h3>
        <form action="{{ route('settings.preferences.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Theme</label>
                    <select name="theme" class="input">
                        <option value="system" {{ $settings->theme === 'system' ? 'selected' : '' }}>System</option>
                        <option value="light" {{ $settings->theme === 'light' ? 'selected' : '' }}>Light</option>
                        <option value="dark" {{ $settings->theme === 'dark' ? 'selected' : '' }}>Dark</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Timezone</label>
                    <select name="timezone" class="input">
                        <option value="UTC" {{ $settings->timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
                        <option value="America/New_York" {{ $settings->timezone === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                        <option value="America/Los_Angeles" {{ $settings->timezone === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                        <option value="Europe/London" {{ $settings->timezone === 'Europe/London' ? 'selected' : '' }}>London</option>
                        <option value="Europe/Paris" {{ $settings->timezone === 'Europe/Paris' ? 'selected' : '' }}>Paris</option>
                        <option value="Asia/Tokyo" {{ $settings->timezone === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo</option>
                        <option value="Asia/Kolkata" {{ $settings->timezone === 'Asia/Kolkata' ? 'selected' : '' }}>India</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Date Format</label>
                    <select name="date_format" class="input">
                        <option value="Y-m-d" {{ $settings->date_format === 'Y-m-d' ? 'selected' : '' }}>2025-12-22</option>
                        <option value="m/d/Y" {{ $settings->date_format === 'm/d/Y' ? 'selected' : '' }}>12/22/2025</option>
                        <option value="d/m/Y" {{ $settings->date_format === 'd/m/Y' ? 'selected' : '' }}>22/12/2025</option>
                        <option value="M j, Y" {{ $settings->date_format === 'M j, Y' ? 'selected' : '' }}>Dec 22, 2025</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                <h4 class="text-sm font-medium text-[var(--text-primary)]">Notifications</h4>
                <div class="flex items-center justify-between p-4 rounded-lg bg-[var(--surface-hover)]">
                    <div>
                        <p class="text-sm font-medium text-[var(--text-primary)]">Email Notifications</p>
                        <p class="text-xs text-[var(--text-muted)]">Receive notifications via email</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="email_notifications" value="1" class="sr-only peer" {{ $settings->email_notifications ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-[var(--surface-alt)] peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[var(--primary)]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--primary)]"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg bg-[var(--surface-hover)]">
                    <div>
                        <p class="text-sm font-medium text-[var(--text-primary)]">Push Notifications</p>
                        <p class="text-xs text-[var(--text-muted)]">Receive browser push notifications</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="push_notifications" value="1" class="sr-only peer" {{ $settings->push_notifications ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-[var(--surface-alt)] peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[var(--primary)]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--primary)]"></div>
                    </label>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary">Save Preferences</button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-6">Change Password</h3>
        <form action="{{ route('settings.password.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Current Password</label>
                    <input type="password" name="current_password" class="input" required>
                    @error('current_password')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">New Password</label>
                    <input type="password" name="password" class="input" required>
                    @error('password')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="input" required>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="card p-6 border-red-500/30" x-data="{ showDeleteModal: false }">
        <h3 class="text-lg font-semibold text-red-500 mb-2">Danger Zone</h3>
        <p class="text-[var(--text-secondary)] text-sm mb-4">
            Permanently delete your account and all associated data. This action cannot be undone.
        </p>
        <button type="button" @click="showDeleteModal = true" class="btn btn-danger">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Delete Account
        </button>

        <!-- Delete Account Modal -->
        <div x-show="showDeleteModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             style="display: none;">
            <div @click.away="showDeleteModal = false" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="w-full max-w-md bg-[var(--surface)] rounded-xl shadow-2xl border border-[var(--border)]">
                <form action="{{ route('settings.account.delete') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--text-primary)]">Delete Account</h3>
                                <p class="text-sm text-[var(--text-muted)]">This action is permanent and irreversible</p>
                            </div>
                        </div>

                        <div class="p-4 mb-4 rounded-lg bg-red-500/10 border border-red-500/20">
                            <p class="text-sm text-red-400">
                                <strong>Warning:</strong> Deleting your account will permanently remove:
                            </p>
                            <ul class="mt-2 text-sm text-red-400/80 list-disc list-inside space-y-1">
                                <li>All connected social media accounts</li>
                                <li>All analytics data and sync history</li>
                                <li>Your profile and preferences</li>
                            </ul>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">
                                    Enter your password to confirm
                                </label>
                                <input type="password" name="password" class="input" required placeholder="Your current password">
                                @error('password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--text-secondary)] mb-2">
                                    Type <span class="font-mono text-red-500">DELETE</span> to confirm
                                </label>
                                <input type="text" name="confirmation" class="input font-mono" required placeholder="DELETE" autocomplete="off">
                                @error('confirmation')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-[var(--border)] bg-[var(--surface-hover)]/50 rounded-b-xl">
                        <button type="button" @click="showDeleteModal = false" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete My Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

