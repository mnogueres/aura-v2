<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * VisitTreatment - Write model for treatments performed during a visit.
 *
 * Represents a specific clinical intervention within a visit.
 * Part of the write side (domain model).
 */
class VisitTreatment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        'type',
        'tooth',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
