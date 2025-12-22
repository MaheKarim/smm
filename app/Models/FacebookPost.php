<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'facebook_page_id',
        'post_id',
        'message',
        'story',
        'post_type',
        'permalink_url',
        'full_picture',
        'is_ad',
        'is_published',
        'scheduled_publish_time',
        'published_at',
    ];

    protected $casts = [
        'is_ad' => 'boolean',
        'is_published' => 'boolean',
        'scheduled_publish_time' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(FacebookAnalytics::class);
    }

    public function getLatestAnalytics()
    {
        return $this->analytics()
            ->where('metric_type', 'post')
            ->orderBy('date', 'desc')
            ->first();
    }

    public function getEngagementScore(): int
    {
        $latest = $this->getLatestAnalytics();
        if (!$latest) return 0;
        
        return $latest->reactions_total + $latest->comments + $latest->shares;
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at');
    }

    public function scopeAds($query)
    {
        return $query->where('is_ad', true);
    }

    public function scopeOrganic($query)
    {
        return $query->where('is_ad', false);
    }
}

