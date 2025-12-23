<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\Analytics\FacebookAnalyticsController;
use App\Http\Controllers\Analytics\YouTubeAnalyticsController;
use App\Http\Controllers\Analytics\InstagramAnalyticsController;
use App\Http\Controllers\Analytics\GoogleAnalyticsController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

// Handle GET request to /logout gracefully (some browsers/extensions try this)
Route::get('/logout', function () {
    if (auth()->check()) {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
    return redirect('/login');
})->name('logout.get');

// OAuth Callback routes (must be accessible before auth)
Route::prefix('auth')->name('oauth.')->group(function () {
    Route::get('facebook/callback', [OAuthController::class, 'facebookCallback'])->name('facebook.callback');
    Route::get('youtube/callback', [OAuthController::class, 'youtubeCallback'])->name('youtube.callback');
    Route::get('instagram/callback', [OAuthController::class, 'instagramCallback'])->name('instagram.callback');
    Route::get('google-analytics/callback', [OAuthController::class, 'googleAnalyticsCallback'])->name('google-analytics.callback');
});

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/facebook', [FacebookAnalyticsController::class, 'index'])->name('facebook');
        Route::get('/facebook/{account}', [FacebookAnalyticsController::class, 'show'])->name('facebook.show');
        Route::get('/youtube', [YouTubeAnalyticsController::class, 'index'])->name('youtube');
        Route::get('/youtube/{account}', [YouTubeAnalyticsController::class, 'show'])->name('youtube.show');
        Route::get('/instagram', [InstagramAnalyticsController::class, 'index'])->name('instagram');
        Route::get('/instagram/{account}', [InstagramAnalyticsController::class, 'show'])->name('instagram.show');
        Route::get('/google-analytics', [GoogleAnalyticsController::class, 'index'])->name('google');
        Route::get('/google-analytics/{account}', [GoogleAnalyticsController::class, 'show'])->name('google.show');
    });

    // Integration Routes
    Route::prefix('integrations')->name('integrations.')->group(function () {
        // Main integrations page
        Route::get('/', [IntegrationController::class, 'index'])->name('index');
        
        // Platform-specific pages
        Route::get('/{platform}', [IntegrationController::class, 'show'])
            ->where('platform', 'facebook|youtube|instagram|google_analytics')
            ->name('show');
        
        // OAuth initiation routes
        Route::get('/facebook/connect', [OAuthController::class, 'facebookRedirect'])->name('facebook.connect');
        Route::get('/youtube/connect', [OAuthController::class, 'youtubeRedirect'])->name('youtube.connect');
        Route::get('/instagram/connect', [OAuthController::class, 'instagramRedirect'])->name('instagram.connect');
        Route::get('/google-analytics/connect', [OAuthController::class, 'googleAnalyticsRedirect'])->name('google-analytics.connect');
        
        // Manual connection routes
        Route::post('/{platform}/connect-token', [IntegrationController::class, 'connectWithToken'])->name('connect.token');
        Route::post('/{platform}/connect-api-key', [IntegrationController::class, 'connectWithApiKey'])->name('connect.api-key');
        Route::post('/{platform}/connect-service-account', [IntegrationController::class, 'connectWithServiceAccount'])->name('connect.service-account');
        
        // Account management
        Route::get('/{socialAccount}/reconnect', [IntegrationController::class, 'reconnect'])->name('reconnect');
        Route::put('/{socialAccount}/update-token', [IntegrationController::class, 'updateToken'])->name('update-token');
        Route::get('/{socialAccount}/test', [IntegrationController::class, 'testConnection'])->name('test');
        Route::get('/{socialAccount}/logs', [IntegrationController::class, 'syncLogs'])->name('logs');
        Route::delete('/{socialAccount}/disconnect', [IntegrationController::class, 'disconnect'])->name('disconnect');
        Route::post('/{socialAccount}/sync', [IntegrationController::class, 'sync'])->name('sync');
        
        // Bulk operations
        Route::post('/sync-all', [IntegrationController::class, 'syncAll'])->name('sync-all');
        Route::post('/instant-refresh', [IntegrationController::class, 'instantRefresh'])->name('instant-refresh');
        Route::get('/sync-status/{jobId}', [IntegrationController::class, 'syncStatus'])->name('sync-status');
    });

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('/preferences', [SettingsController::class, 'updatePreferences'])->name('preferences.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::delete('/account', [SettingsController::class, 'deleteAccount'])->name('account.delete');
    });
});
