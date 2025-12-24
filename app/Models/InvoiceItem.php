<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'invoice_id',
        'treatment_id',
        'description',
        'quantity',
        'unit_price',
        'tax_percent',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
