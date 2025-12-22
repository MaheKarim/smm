<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'page_id',
        'name',
        'username',
        'category',
        'followers_count',
        'likes_count',
        'profile_picture_url',
        'cover_photo_url',
        'website',
        'about',
        'is_published',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'likes_count' => 'integer',
        'is_published' => 'boolean',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(FacebookAnalytics::class);
    }

    public function pageAnalytics(): HasMany
    {
        return $this->hasMany(FacebookAnalytics::class)->where('metric_type', 'page');
    }

    public function getLatestAnalytics(int $days = 30)
    {
        return $this->analytics()
            ->where('metric_type', 'page')
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getTotalEngagement(int $days = 30): int
    {
        return $this->analytics()
            ->where('metric_type', 'page')
            ->where('date', '>=', now()->subDays($days))
            ->sum('reactions_total') + 
            $this->analytics()
            ->where('metric_type', 'page')
            ->where('date', '>=', now()->subDays($days))
            ->sum('comments') +
            $this->analytics()
            ->where('metric_type', 'page')
            ->where('date', '>=', now()->subDays($days))
            ->sum('shares');
    }
}

