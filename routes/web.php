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
        Route::get('/youtube', [YouTubeAnalyticsController::class, 'index'])->name('youtube');
        Route::get('/instagram', [InstagramAnalyticsController::class, 'index'])->name('instagram');
        Route::get('/google-analytics', [GoogleAnalyticsController::class, 'index'])->name('google');
    });

    // Integration Routes
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [IntegrationController::class, 'index'])->name('index');
        
        // OAuth initiation routes
        Route::get('/facebook/connect', [OAuthController::class, 'facebookRedirect'])->name('facebook.connect');
        Route::get('/youtube/connect', [OAuthController::class, 'youtubeRedirect'])->name('youtube.connect');
        Route::get('/instagram/connect', [OAuthController::class, 'instagramRedirect'])->name('instagram.connect');
        Route::get('/google-analytics/connect', [OAuthController::class, 'googleAnalyticsRedirect'])->name('google-analytics.connect');
        
        // Disconnect routes
        Route::delete('/{socialAccount}/disconnect', [IntegrationController::class, 'disconnect'])->name('disconnect');
        
        // Sync routes
        Route::post('/{socialAccount}/sync', [IntegrationController::class, 'sync'])->name('sync');
        Route::post('/sync-all', [IntegrationController::class, 'syncAll'])->name('sync-all');
    });

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('/preferences', [SettingsController::class, 'updatePreferences'])->name('preferences.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
    });
});
