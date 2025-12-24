<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id'       => ['required', 'exists:clinics,id'],
            'patient_id'      => ['required', 'exists:patients,id'],
            'invoice_id'      => ['nullable', 'exists:invoices,id'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'payment_date'    => ['required', 'date'],
            'payment_method'  => ['required', 'in:cash,card,transfer,other'],
            'notes'           => ['nullable', 'string'],
        ];
    }
}
