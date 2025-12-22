<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookAnalytics extends Model
{
    use HasFactory;

    protected $table = 'facebook_analytics';

    protected $fillable = [
        'facebook_page_id',
        'facebook_post_id',
        'date',
        'metric_type',
        'impressions',
        'reach',
        'engaged_users',
        'reactions_total',
        'reactions_like',
        'reactions_love',
        'reactions_haha',
        'reactions_wow',
        'reactions_sad',
        'reactions_angry',
        'comments',
        'shares',
        'clicks',
        'video_views',
        'video_view_time',
        'ctr',
        'engagement_rate',
        'negative_feedback',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'engaged_users' => 'integer',
        'reactions_total' => 'integer',
        'reactions_like' => 'integer',
        'reactions_love' => 'integer',
        'reactions_haha' => 'integer',
        'reactions_wow' => 'integer',
        'reactions_sad' => 'integer',
        'reactions_angry' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'clicks' => 'integer',
        'video_views' => 'integer',
        'video_view_time' => 'integer',
        'ctr' => 'decimal:4',
        'engagement_rate' => 'decimal:4',
        'negative_feedback' => 'integer',
    ];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class);
    }

    public function facebookPost(): BelongsTo
    {
        return $this->belongsTo(FacebookPost::class);
    }

    public function getTotalEngagement(): int
    {
        return $this->reactions_total + $this->comments + $this->shares;
    }

    // Scopes
    public function scopePageMetrics($query)
    {
        return $query->where('metric_type', 'page');
    }

    public function scopePostMetrics($query)
    {
        return $query->where('metric_type', 'post');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}

