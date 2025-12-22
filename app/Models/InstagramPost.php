<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_account_id',
        'media_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'permalink',
        'caption',
        'hashtags',
        'location',
        'is_story',
        'story_expires_at',
        'published_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'is_story' => 'boolean',
        'story_expires_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(InstagramAnalytics::class);
    }

    public function getLatestAnalytics()
    {
        return $this->analytics()
            ->whereIn('metric_type', ['post', 'story', 'reel'])
            ->orderBy('date', 'desc')
            ->first();
    }

    public function getEngagementScore(): int
    {
        $latest = $this->getLatestAnalytics();
        if (!$latest) return 0;
        
        return $latest->likes + $latest->comments + $latest->saves + $latest->shares;
    }

    public function isReel(): bool
    {
        return $this->media_type === 'REELS';
    }

    public function isVideo(): bool
    {
        return in_array($this->media_type, ['VIDEO', 'REELS']);
    }

    // Scopes
    public function scopeReels($query)
    {
        return $query->where('media_type', 'REELS');
    }

    public function scopeImages($query)
    {
        return $query->where('media_type', 'IMAGE');
    }

    public function scopeCarousels($query)
    {
        return $query->where('media_type', 'CAROUSEL_ALBUM');
    }

    public function scopeStories($query)
    {
        return $query->where('is_story', true);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }
}

