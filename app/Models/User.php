<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    // Helper methods for social accounts
    public function facebookAccounts(): HasMany
    {
        return $this->socialAccounts()->where('platform', 'facebook');
    }

    public function youtubeAccounts(): HasMany
    {
        return $this->socialAccounts()->where('platform', 'youtube');
    }

    public function instagramAccounts(): HasMany
    {
        return $this->socialAccounts()->where('platform', 'instagram');
    }

    public function googleAnalyticsAccounts(): HasMany
    {
        return $this->socialAccounts()->where('platform', 'google_analytics');
    }

    public function activeSocialAccounts(): HasMany
    {
        return $this->socialAccounts()->where('status', 'active');
    }

    public function getConnectedPlatforms(): array
    {
        return $this->socialAccounts()
            ->where('status', 'active')
            ->pluck('platform')
            ->unique()
            ->values()
            ->toArray();
    }

    public function getAccountCountByPlatform(): array
    {
        return $this->socialAccounts()
            ->where('status', 'active')
            ->get()
            ->groupBy('platform')
            ->map->count()
            ->toArray();
    }

    public function getOrCreateSettings(): UserSetting
    {
        return $this->settings ?? $this->settings()->create([
            'theme' => 'system',
            'timezone' => 'UTC',
        ]);
    }
}
