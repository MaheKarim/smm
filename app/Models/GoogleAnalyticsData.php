<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleAnalyticsData extends Model
{
    use HasFactory;

    protected $table = 'google_analytics_data';

    protected $fillable = [
        'google_analytics_property_id',
        'date',
        'dimension_type',
        'dimension_value',
        'sessions',
        'total_users',
        'new_users',
        'active_users',
        'pageviews',
        'screens_per_session',
        'average_session_duration',
        'bounce_rate',
        'engagement_rate',
        'engaged_sessions',
        'events_count',
        'conversions',
        'total_revenue',
        'ecommerce_purchases',
    ];

    protected $casts = [
        'date' => 'date',
        'sessions' => 'integer',
        'total_users' => 'integer',
        'new_users' => 'integer',
        'active_users' => 'integer',
        'pageviews' => 'integer',
        'screens_per_session' => 'decimal:2',
        'average_session_duration' => 'decimal:2',
        'bounce_rate' => 'decimal:4',
        'engagement_rate' => 'decimal:4',
        'engaged_sessions' => 'integer',
        'events_count' => 'integer',
        'conversions' => 'integer',
        'total_revenue' => 'decimal:2',
        'ecommerce_purchases' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(GoogleAnalyticsProperty::class, 'google_analytics_property_id');
    }

    public function getFormattedSessionDuration(): string
    {
        $seconds = (int) $this->average_session_duration;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function getBounceRatePercentage(): float
    {
        return round($this->bounce_rate * 100, 2);
    }

    public function getEngagementRatePercentage(): float
    {
        return round($this->engagement_rate * 100, 2);
    }

    // Scopes
    public function scopeOverview($query)
    {
        return $query->whereNull('dimension_type');
    }

    public function scopeByDimension($query, string $dimension)
    {
        return $query->where('dimension_type', $dimension);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}

