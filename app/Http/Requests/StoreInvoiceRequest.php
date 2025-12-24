<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id'      => ['required', 'exists:clinics,id'],
            'patient_id'     => ['required', 'exists:patients,id'],
            'invoice_number' => ['required', 'string', 'max:50'],
            'invoice_date'   => ['required', 'date'],
            'status'         => ['required', 'in:draft,issued,paid,cancelled'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
