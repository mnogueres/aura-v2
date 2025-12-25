<?php

namespace App\Http\Controllers;

use App\Repositories\PatientSummaryRepository;
use App\Models\PatientTimeline;
use App\Models\BillingTimeline;
use Illuminate\Http\Request;

class PatientWorkspaceController extends Controller
{
    public function __construct(
        private readonly PatientSummaryRepository $summaryRepository
    ) {
    }

    public function show(Request $request, int $patientId)
    {
        // Obtener clinic_id (temporal para demo - usar el último creado)
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Fetch patient model (READ ONLY - for identity display)
        $patient = \App\Models\Patient::where('clinic_id', $clinicId)
            ->where('id', $patientId)
            ->first();

        if (!$patient) {
            abort(404, 'Patient not found');
        }

        // Fetch patient summary
        $summary = $this->summaryRepository->getByPatient($clinicId, $patientId);

        // Fetch patient timeline (with pagination)
        $timelinePage = $request->query('timeline_page', 1);
        $timelineQuery = PatientTimeline::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at');

        $timelinePaginator = $timelineQuery->paginate(25, ['*'], 'timeline_page', $timelinePage);
        $timeline = $timelinePaginator->items();
        $timelineMeta = [
            'current_page' => $timelinePaginator->currentPage(),
            'last_page' => $timelinePaginator->lastPage(),
            'per_page' => $timelinePaginator->perPage(),
            'total' => $timelinePaginator->total(),
        ];

        // Fetch billing timeline (with pagination)
        $billingPage = $request->query('billing_page', 1);
        $billingQuery = BillingTimeline::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at');

        $billingPaginator = $billingQuery->paginate(25, ['*'], 'billing_page', $billingPage);
        $billing = $billingPaginator->items();
        $billingMeta = [
            'current_page' => $billingPaginator->currentPage(),
            'last_page' => $billingPaginator->lastPage(),
            'per_page' => $billingPaginator->perPage(),
            'total' => $billingPaginator->total(),
        ];

        return view('workspace.patient.show', compact(
            'patient',
            'patientId',
            'summary',
            'timeline',
            'timelineMeta',
            'billing',
            'billingMeta'
        ));
    }
}
