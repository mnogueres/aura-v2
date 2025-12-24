<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'invoice_number',
        'invoice_date',
        'status',
        'notes',
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

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function subtotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    public function taxTotal(): float
    {
        return $this->items->sum(function ($item) {
            $lineTotal = $item->quantity * $item->unit_price;
            return $lineTotal * ($item->tax_percent / 100);
        });
    }

    public function total(): float
    {
        return $this->subtotal() + $this->taxTotal();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOutstanding(): bool
    {
        return $this->isIssued() && $this->total() > 0;
    }

    public function paidAmount(): float
    {
        return $this->payments->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, $this->total() - $this->paidAmount());
    }
}
