<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    use HasUuids;

    protected $table = 'audit_trail';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'event_name',
        'category',
        'severity',
        'actor_type',
        'actor_id',
        'context',
        'occurred_at',
        'projected_at',
        'source_event_id',
    ];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
        'projected_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
