<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'instagram_id',
        'username',
        'name',
        'biography',
        'profile_picture_url',
        'website',
        'followers_count',
        'follows_count',
        'media_count',
        'account_type',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(InstagramPost::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(InstagramAnalytics::class);
    }

    public function accountAnalytics(): HasMany
    {
        return $this->hasMany(InstagramAnalytics::class)->where('metric_type', 'account');
    }

    public function getLatestAnalytics(int $days = 30)
    {
        return $this->analytics()
            ->where('metric_type', 'account')
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getTotalReach(int $days = 30): int
    {
        return $this->analytics()
            ->where('metric_type', 'account')
            ->where('date', '>=', now()->subDays($days))
            ->sum('reach');
    }

    public function getTotalEngagement(int $days = 30): int
    {
        return $this->analytics()
            ->where('date', '>=', now()->subDays($days))
            ->sum('likes') +
            $this->analytics()
            ->where('date', '>=', now()->subDays($days))
            ->sum('comments') +
            $this->analytics()
            ->where('date', '>=', now()->subDays($days))
            ->sum('saves') +
            $this->analytics()
            ->where('date', '>=', now()->subDays($days))
            ->sum('shares');
    }
}

