<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * EventOutbox - Outbox Pattern for domain event persistence.
 *
 * This table stores events after they are emitted post-commit,
 * providing durability, retry capability, and preparation for
 * future async processing.
 *
 * Status values:
 * - pending: Event recorded but not yet processed
 * - processed: Event successfully processed
 * - failed: Event processing failed after max attempts
 */
class EventOutbox extends Model
{
    use HasUuids;

    protected $table = 'event_outbox';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'clinic_id',
        'event_name',
        'payload',
        'occurred_at',
        'recorded_at',
        'status',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'recorded_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Get clinic relationship.
     */
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Scope to get pending events.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed events.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark event as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
        ]);
    }

    /**
     * Mark event as failed with error message.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'attempts' => $this->attempts + 1,
            'last_error' => $error,
        ]);
    }

    /**
     * Increment attempt counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
