<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YouTubeVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'youtube_channel_id',
        'video_id',
        'title',
        'description',
        'thumbnail_url',
        'duration',
        'duration_seconds',
        'category_id',
        'tags',
        'privacy_status',
        'is_live_content',
        'is_short',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'duration_seconds' => 'integer',
        'is_live_content' => 'boolean',
        'is_short' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function youtubeChannel(): BelongsTo
    {
        return $this->belongsTo(YouTubeChannel::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(YouTubeAnalytics::class);
    }

    public function getLatestAnalytics()
    {
        return $this->analytics()
            ->where('metric_type', 'video')
            ->orderBy('date', 'desc')
            ->first();
    }

    public function getTotalViews(): int
    {
        return $this->analytics()
            ->where('metric_type', 'video')
            ->sum('views');
    }

    public function getFormattedDuration(): string
    {
        $seconds = $this->duration_seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('privacy_status', 'public');
    }

    public function scopeShorts($query)
    {
        return $query->where('is_short', true);
    }

    public function scopeRegularVideos($query)
    {
        return $query->where('is_short', false);
    }
}

