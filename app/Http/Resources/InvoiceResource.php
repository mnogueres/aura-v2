<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'invoice_number'=> $this->invoice_number,
            'invoice_date'  => $this->invoice_date,
            'status'        => $this->status,

            // Derived amounts (no persistence)
            'subtotal'      => $this->subtotal(),
            'tax_total'     => $this->taxTotal(),
            'total'         => $this->total(),
            'paid_amount'   => $this->paidAmount(),
            'balance_due'   => $this->balanceDue(),

            'patient'       => [
                'id'         => $this->patient->id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
            ],

            'created_at'    => $this->created_at,
        ];
    }
}
