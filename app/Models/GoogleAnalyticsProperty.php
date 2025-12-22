<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleAnalyticsProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'property_id',
        'property_name',
        'property_type',
        'account_id',
        'account_name',
        'currency_code',
        'time_zone',
        'industry_category',
        'service_level',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function analyticsData(): HasMany
    {
        return $this->hasMany(GoogleAnalyticsData::class);
    }

    public function getLatestAnalytics(int $days = 30)
    {
        return $this->analyticsData()
            ->whereNull('dimension_type')
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getTotalSessions(int $days = 30): int
    {
        return $this->analyticsData()
            ->whereNull('dimension_type')
            ->where('date', '>=', now()->subDays($days))
            ->sum('sessions');
    }

    public function getTotalUsers(int $days = 30): int
    {
        return $this->analyticsData()
            ->whereNull('dimension_type')
            ->where('date', '>=', now()->subDays($days))
            ->sum('total_users');
    }

    public function getTotalPageviews(int $days = 30): int
    {
        return $this->analyticsData()
            ->whereNull('dimension_type')
            ->where('date', '>=', now()->subDays($days))
            ->sum('pageviews');
    }

    public function getAverageBounceRate(int $days = 30): float
    {
        $avg = $this->analyticsData()
            ->whereNull('dimension_type')
            ->where('date', '>=', now()->subDays($days))
            ->avg('bounce_rate');
        
        return $avg ? round($avg * 100, 2) : 0;
    }
}

