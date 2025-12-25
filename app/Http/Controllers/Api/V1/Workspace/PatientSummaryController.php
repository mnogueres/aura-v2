<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Controller;
use App\Repositories\PatientSummaryRepository;
use Illuminate\Http\JsonResponse;

class PatientSummaryController extends Controller
{
    public function __construct(
        private readonly PatientSummaryRepository $repository
    ) {
    }

    public function show(int $patientId): JsonResponse
    {
        $clinicId = app('currentClinicId');

        $summary = $this->repository->getByPatient($clinicId, $patientId);

        if (!$summary) {
            return response()->apiError(
                'patient_summary_not_found',
                'Patient summary not found',
                404
            );
        }

        return response()->api($summary);
    }
}
