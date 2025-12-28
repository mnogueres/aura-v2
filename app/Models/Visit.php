<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Visit - Write model for clinical visits.
 *
 * Represents the physical encounter between a patient and a professional.
 * Part of the write side (domain model).
 */
class Visit extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'professional_id',
        'occurred_at',
        'visit_type',
        'summary',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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

    public function professional()
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function treatments()
    {
        return $this->hasMany(VisitTreatment::class);
    }
}
