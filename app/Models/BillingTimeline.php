<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingTimeline extends Model
{
    use HasUuids;

    protected $table = 'billing_timeline';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'event_name',
        'amount',
        'currency',
        'reference_id',
        'event_payload',
        'occurred_at',
        'projected_at',
        'source_event_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'event_payload' => 'array',
        'occurred_at' => 'datetime',
        'projected_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
