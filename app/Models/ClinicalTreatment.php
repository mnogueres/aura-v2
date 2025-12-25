<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClinicalTreatment extends Model
{
    use HasUuids;

    protected $table = 'clinical_treatments';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'clinic_id',
        'patient_id',
        'visit_id',
        'type',
        'tooth',
        'amount',
        'notes',
        'projected_at',
        'source_event_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'projected_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(ClinicalVisit::class, 'visit_id');
    }
}
