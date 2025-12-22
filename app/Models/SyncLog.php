<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id',
        'platform',
        'sync_type',
        'status',
        'records_synced',
        'error_message',
        'error_code',
        'started_at',
        'completed_at',
        'duration_seconds',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(int $recordsSynced = 0): void
    {
        $startedAt = $this->started_at ?? now();
        $this->update([
            'status' => 'completed',
            'records_synced' => $recordsSynced,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($startedAt),
        ]);
    }

    public function markAsFailed(string $message, ?string $code = null): void
    {
        $startedAt = $this->started_at ?? now();
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
            'error_code' => $code,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($startedAt),
        ]);
    }

    // Scopes
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

