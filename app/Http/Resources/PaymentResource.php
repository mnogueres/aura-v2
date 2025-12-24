<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'amount'        => $this->amount,
            'payment_date'  => $this->payment_date->toDateString(),
            'payment_method'=> $this->payment_method,
            'notes'         => $this->notes,

            'patient'       => [
                'id'         => $this->patient->id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
            ],

            'invoice'       => $this->invoice ? [
                'id'             => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
            ] : null,

            'created_at'    => $this->created_at,
        ];
    }
}
