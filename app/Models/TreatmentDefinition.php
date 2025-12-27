<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TreatmentDefinition - Write model for treatment catalog (FASE 20.5)
 *
 * Stores stable treatment definitions per clinic with reference pricing.
 * The default_price is a reference only - actual treatment prices can differ.
 *
 * @property string $id
 * @property int $clinic_id
 * @property string $name
 * @property float|null $default_price
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class TreatmentDefinition extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'name',
        'default_price',
        'active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Treatment definition belongs to a clinic
     */
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Relationship: Visit treatments that reference this definition
     */
    public function visitTreatments()
    {
        return $this->hasMany(VisitTreatment::class, 'treatment_definition_id');
    }
}
