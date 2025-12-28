<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;

/**
 * ClinicalProfessional - Read model for professional projections.
 *
 * Derived from clinical.professional.* events.
 * Optimized for querying and display.
 * No timestamps - uses projected_at instead.
 */
class ClinicalProfessional extends Model
{
    use HasUuids;

    protected $table = 'clinical_professionals';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'clinic_id',
        'name',
        'role',
        'active',
        'user_id',
        'projected_at',
        'source_event_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'projected_at' => 'datetime',
    ];

    /**
     * Scope to filter only active professionals
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope to order alphabetically by name
     */
    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to filter by clinic
     */
    public function scopeForClinic(Builder $query, int $clinicId): Builder
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
