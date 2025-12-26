<?php

namespace App\Http\Controllers;

use App\Repositories\PatientSummaryRepository;
use App\Repositories\ClinicalVisitRepository;
use App\Repositories\ClinicalTreatmentRepository;
use App\Models\PatientTimeline;
use App\Models\BillingTimeline;
use Illuminate\Http\Request;

class PatientWorkspaceController extends Controller
{
    public function __construct(
        private readonly PatientSummaryRepository $summaryRepository,
        private readonly ClinicalVisitRepository $clinicalVisitRepository,
        private readonly ClinicalTreatmentRepository $clinicalTreatmentRepository
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

        // Fetch clinical visits with pagination (FASE 17+)
        $visitsPage = $request->query('visits_page', 1);
        $visitsPaginator = $this->clinicalVisitRepository->getVisitsForPatientPaginated(
            $clinicId,
            $patientId,
            8,  // per page
            $visitsPage
        );

        $clinicalVisits = $visitsPaginator->items();
        $visitsMeta = [
            'current_page' => $visitsPaginator->currentPage(),
            'last_page' => $visitsPaginator->lastPage(),
            'per_page' => $visitsPaginator->perPage(),
            'total' => $visitsPaginator->total(),
        ];

        // Load treatments for each visit
        foreach ($clinicalVisits as $visit) {
            $visit->treatments = $this->clinicalTreatmentRepository->getTreatmentsForVisit($visit->id);
        }

        // FASE 17: Timeline técnico no se muestra en Workspace (código preservado)
        /*
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
        */

        // Defaults vacíos para evitar errores de vista
        $timeline = [];
        $timelineMeta = ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 0];

        // Fetch billing timeline (with pagination)
        $billingPage = $request->query('billing_page', 1);
        $billingQuery = BillingTimeline::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at');

        $billingPaginator = $billingQuery->paginate(8, ['*'], 'billing_page', $billingPage);
        $billing = $billingPaginator->items();
        $billingMeta = [
            'current_page' => $billingPaginator->currentPage(),
            'last_page' => $billingPaginator->lastPage(),
            'per_page' => $billingPaginator->perPage(),
            'total' => $billingPaginator->total(),
        ];

        // FASE 19.1: Si es petición parcial (HTMX), devolver solo el contenido
        if ($request->has('partial')) {
            $partial = $request->get('partial');

            if ($partial === 'billing') {
                return view('workspace.patient.partials._billing_content', compact(
                    'billing',
                    'billingMeta',
                    'patientId'
                ));
            }

            if ($partial === 'visits') {
                return view('workspace.patient.partials._visits_content', compact(
                    'clinicalVisits',
                    'visitsMeta',
                    'patientId'
                ));
            }
        }

        // Vista completa normal
        return view('workspace.patient.show', compact(
            'patient',
            'patientId',
            'summary',
            'clinicalVisits',
            'visitsMeta',
            'timeline',
            'timelineMeta',
            'billing',
            'billingMeta'
        ));
    }
}
