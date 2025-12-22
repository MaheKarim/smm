<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouTubeAnalytics extends Model
{
    use HasFactory;

    protected $table = 'youtube_analytics';

    protected $fillable = [
        'youtube_channel_id',
        'youtube_video_id',
        'date',
        'metric_type',
        'views',
        'watch_time_minutes',
        'average_view_duration',
        'average_view_percentage',
        'subscribers_gained',
        'subscribers_lost',
        'likes',
        'dislikes',
        'comments',
        'shares',
        'estimated_revenue',
        'impressions',
        'impressions_ctr',
        'unique_viewers',
        'traffic_source_data',
        'device_type_data',
        'geography_data',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'watch_time_minutes' => 'integer',
        'average_view_duration' => 'decimal:2',
        'average_view_percentage' => 'decimal:2',
        'subscribers_gained' => 'integer',
        'subscribers_lost' => 'integer',
        'likes' => 'integer',
        'dislikes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'estimated_revenue' => 'decimal:2',
        'impressions' => 'integer',
        'impressions_ctr' => 'decimal:4',
        'unique_viewers' => 'integer',
        'traffic_source_data' => 'array',
        'device_type_data' => 'array',
        'geography_data' => 'array',
    ];

    public function youtubeChannel(): BelongsTo
    {
        return $this->belongsTo(YouTubeChannel::class);
    }

    public function youtubeVideo(): BelongsTo
    {
        return $this->belongsTo(YouTubeVideo::class);
    }

    public function getNetSubscribers(): int
    {
        return $this->subscribers_gained - $this->subscribers_lost;
    }

    public function getWatchTimeHours(): float
    {
        return round($this->watch_time_minutes / 60, 2);
    }

    // Scopes
    public function scopeChannelMetrics($query)
    {
        return $query->where('metric_type', 'channel');
    }

    public function scopeVideoMetrics($query)
    {
        return $query->where('metric_type', 'video');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}

