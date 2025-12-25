<?php

namespace App\Services;

use App\Events\CRM\PatientCreated;
use App\Models\Patient;

/**
 * PatientService - Business logic for Patient operations.
 *
 * Emits:
 * - crm.patient.created
 */
class PatientService
{
    public function __construct(
        private readonly EventService $eventService
    ) {
    }

    /**
     * Create a new patient and emit PatientCreated event.
     *
     * @param array $data
     * @return Patient
     */
    public function create(array $data): Patient
    {
        $patient = Patient::create($data);

        $this->eventService->emit(
            new PatientCreated(
                patient_id: $patient->id
            )
        );

        return $patient;
    }
}
