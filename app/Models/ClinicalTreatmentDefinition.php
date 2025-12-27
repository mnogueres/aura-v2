<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ClinicalTreatmentDefinition - Read model for treatment catalog (FASE 20.5)
 *
 * Projected from TreatmentDefinition events.
 * Optimized for UI queries - alphabetically ordered for selection dropdowns.
 * Historical data preserved (no hard deletes, only active flag).
 *
 * @property string $id
 * @property int $clinic_id
 * @property string $name
 * @property float|null $default_price
 * @property bool $active
 * @property \Carbon\Carbon $projected_at
 * @property string|null $source_event_id
 */
class ClinicalTreatmentDefinition extends Model
{
    const UPDATED_AT = null;
    const CREATED_AT = null;

    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'clinic_id',
        'name',
        'default_price',
        'active',
        'projected_at',
        'source_event_id',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'active' => 'boolean',
        'projected_at' => 'datetime',
    ];

    /**
     * Relationship: Treatment definition belongs to a clinic
     */
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Scope: Get only active definitions
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Order alphabetically by name
     */
    public function scopeAlphabetical($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope: Filter by clinic
     */
    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }
}
