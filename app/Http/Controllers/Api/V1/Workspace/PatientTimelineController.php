<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Controller;
use App\Models\PatientTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientTimelineController extends Controller
{
    public function index(Request $request, int $patientId): JsonResponse
    {
        $clinicId = app('currentClinicId');

        $timeline = PatientTimeline::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at')
            ->paginate(25);

        return response()->api(
            $timeline->items(),
            200,
            [
                'pagination' => [
                    'total' => $timeline->total(),
                    'per_page' => $timeline->perPage(),
                    'current_page' => $timeline->currentPage(),
                    'last_page' => $timeline->lastPage(),
                ],
            ]
        );
    }
}
