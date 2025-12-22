<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAnalytics extends Model
{
    use HasFactory;

    protected $table = 'instagram_analytics';

    protected $fillable = [
        'instagram_account_id',
        'instagram_post_id',
        'date',
        'metric_type',
        'impressions',
        'reach',
        'profile_views',
        'website_clicks',
        'email_contacts',
        'phone_call_clicks',
        'followers_gained',
        'likes',
        'comments',
        'saves',
        'shares',
        'video_views',
        'engagement_rate',
        'story_exits',
        'story_replies',
        'story_taps_forward',
        'story_taps_back',
        'audience_demographics',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'profile_views' => 'integer',
        'website_clicks' => 'integer',
        'email_contacts' => 'integer',
        'phone_call_clicks' => 'integer',
        'followers_gained' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'saves' => 'integer',
        'shares' => 'integer',
        'video_views' => 'integer',
        'engagement_rate' => 'decimal:4',
        'story_exits' => 'integer',
        'story_replies' => 'integer',
        'story_taps_forward' => 'integer',
        'story_taps_back' => 'integer',
        'audience_demographics' => 'array',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function instagramPost(): BelongsTo
    {
        return $this->belongsTo(InstagramPost::class);
    }

    public function getTotalEngagement(): int
    {
        return $this->likes + $this->comments + $this->saves + $this->shares;
    }

    public function getStoryRetention(): float
    {
        $exits = $this->story_exits;
        $impressions = $this->impressions;
        
        if ($impressions === 0) return 0;
        
        return round((1 - ($exits / $impressions)) * 100, 2);
    }

    // Scopes
    public function scopeAccountMetrics($query)
    {
        return $query->where('metric_type', 'account');
    }

    public function scopePostMetrics($query)
    {
        return $query->where('metric_type', 'post');
    }

    public function scopeStoryMetrics($query)
    {
        return $query->where('metric_type', 'story');
    }

    public function scopeReelMetrics($query)
    {
        return $query->where('metric_type', 'reel');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}

