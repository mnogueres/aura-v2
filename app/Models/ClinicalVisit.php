<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClinicalVisit extends Model
{
    use HasUuids;

    protected $table = 'clinical_visits';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'clinic_id',
        'patient_id',
        'occurred_at',
        'professional_name',
        'visit_type',
        'summary',
        'treatments_count',
        'projected_at',
        'source_event_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'projected_at' => 'datetime',
        'treatments_count' => 'integer',
    ];

    public function treatments()
    {
        return $this->hasMany(ClinicalTreatment::class, 'visit_id');
    }
}
