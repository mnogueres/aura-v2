<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treatment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'name',
        'description',
        'price',
        'active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
