<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YouTubeChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'channel_id',
        'title',
        'description',
        'custom_url',
        'thumbnail_url',
        'banner_url',
        'country',
        'subscriber_count',
        'video_count',
        'view_count',
        'is_monetized',
        'published_at',
    ];

    protected $casts = [
        'subscriber_count' => 'integer',
        'video_count' => 'integer',
        'view_count' => 'integer',
        'is_monetized' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(YouTubeVideo::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(YouTubeAnalytics::class);
    }

    public function channelAnalytics(): HasMany
    {
        return $this->hasMany(YouTubeAnalytics::class)->where('metric_type', 'channel');
    }

    public function getLatestAnalytics(int $days = 30)
    {
        return $this->analytics()
            ->where('metric_type', 'channel')
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getTotalViews(int $days = 30): int
    {
        return $this->analytics()
            ->where('metric_type', 'channel')
            ->where('date', '>=', now()->subDays($days))
            ->sum('views');
    }

    public function getTotalWatchTimeMinutes(int $days = 30): int
    {
        return $this->analytics()
            ->where('metric_type', 'channel')
            ->where('date', '>=', now()->subDays($days))
            ->sum('watch_time_minutes');
    }

    public function getSubscribersGained(int $days = 30): int
    {
        return $this->analytics()
            ->where('metric_type', 'channel')
            ->where('date', '>=', now()->subDays($days))
            ->sum('subscribers_gained');
    }
}

