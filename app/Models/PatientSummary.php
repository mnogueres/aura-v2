<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSummary extends Model
{
    use HasUuids;

    protected $table = 'patient_summary';

    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'created_at_occurred',
        'last_activity_at',
        'invoices_count',
        'payments_count',
        'total_invoiced_amount',
        'total_paid_amount',
        'projected_at',
    ];

    protected $casts = [
        'created_at_occurred' => 'datetime',
        'last_activity_at' => 'datetime',
        'invoices_count' => 'integer',
        'payments_count' => 'integer',
        'total_invoiced_amount' => 'decimal:2',
        'total_paid_amount' => 'decimal:2',
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
