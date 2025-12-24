<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'dni'        => $this->dni,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'birth_date' => $this->birth_date,
            'notes'      => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
