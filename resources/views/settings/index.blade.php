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
    <div class="card p-6 border-red-500/20">
        <h3 class="text-lg font-semibold text-red-500 mb-4">Danger Zone</h3>
        <p class="text-[var(--text-secondary)] mb-4">Once you delete your account, there is no going back. Please be certain.</p>
        <button type="button" class="btn btn-danger" onclick="alert('This feature is not implemented yet.')">
            Delete Account
        </button>
    </div>
</div>
@endsection

