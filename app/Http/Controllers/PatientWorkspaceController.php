<?php

namespace App\Http\Controllers;

use App\Models\BillingTimeline;
use App\Models\PatientTimeline;
use App\Models\Visit;
use App\Models\VisitTreatment;
use App\Repositories\PatientSummaryRepository;
use App\Repositories\ClinicalVisitRepository;
use App\Repositories\ClinicalTreatmentRepository;
use App\Services\ClinicalVisitService;
use App\Services\OutboxEventConsumer;
use Illuminate\Http\Request;

class PatientWorkspaceController extends Controller
{
    public function __construct(
        private readonly PatientSummaryRepository $summaryRepository,
        private readonly ClinicalVisitRepository $clinicalVisitRepository,
        private readonly ClinicalTreatmentRepository $clinicalTreatmentRepository,
        private readonly ClinicalVisitService $clinicalVisitService,
        private readonly \App\Services\ClinicalTreatmentService $clinicalTreatmentService,
        private readonly OutboxEventConsumer $outboxConsumer
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

        // FASE 20.5: Load active treatment definitions for catalog selection
        $treatmentDefinitions = \App\Models\ClinicalTreatmentDefinition::forClinic($clinicId)
            ->active()
            ->alphabetical()
            ->get();

        // FASE 21.0: Load active professionals for visit assignment
        $professionals = \App\Models\ClinicalProfessional::forClinic($clinicId)
            ->active()
            ->alphabetical()
            ->get();

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
                    'patientId',
                    'treatmentDefinitions', // FASE 20.5
                    'professionals' // FASE 21.0
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
            'billingMeta',
            'treatmentDefinitions', // FASE 20.5
            'professionals' // FASE 21.0
        ));
    }

    /**
     * Store a new clinical visit (FASE 20.2).
     *
     * Internal write endpoint - NOT part of public API v1.
     */
    public function storeVisit(Request $request, int $patientId)
    {
        // Get clinic_id from context
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Verify patient exists
        $patient = \App\Models\Patient::where('clinic_id', $clinicId)
            ->where('id', $patientId)
            ->first();

        if (!$patient) {
            abort(404, 'Patient not found');
        }

        // Validate input (FASE 21.1: professional_id now references professionals table with UUID)
        $validated = $request->validate([
            'occurred_at' => 'required|date',
            'visit_type' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'professional_id' => 'nullable|uuid|exists:professionals,id',
        ]);

        try {
            // Use ClinicalVisitService (CQRS write side)
            $visit = $this->clinicalVisitService->createVisit([
                'clinic_id' => $clinicId,
                'patient_id' => $patientId,
                'professional_id' => $validated['professional_id'] ?? null,
                'occurred_at' => $validated['occurred_at'],
                'visit_type' => $validated['visit_type'] ?? null,
                'summary' => $validated['summary'] ?? null,
            ]);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // HTMX response: refresh visits partial
            $visitsPage = 1; // Always show first page after creation
            $visitsPaginator = $this->clinicalVisitRepository->getVisitsForPatientPaginated(
                $clinicId,
                $patientId,
                8,
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
            foreach ($clinicalVisits as $v) {
                $v->treatments = $this->clinicalTreatmentRepository->getTreatmentsForVisit($v->id);
            }

            // FASE 20.5: Load active treatment definitions for catalog selection
            $treatmentDefinitions = \App\Models\ClinicalTreatmentDefinition::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            // FASE 21.1: Load active professionals for visit assignment
            $professionals = \App\Models\ClinicalProfessional::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            return view('workspace.patient.partials._visits_content', compact(
                'clinicalVisits',
                'visitsMeta',
                'patientId',
                'treatmentDefinitions', // FASE 20.5
                'professionals' // FASE 21.1
            ));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Add a treatment to an existing visit (FASE 20.3).
     *
     * Internal write endpoint - NOT part of public API v1.
     *
     * FASE 20.6 bug fix: Returns OOB swap to update visit header
     * (syncs treatments_count so delete visit button disables correctly)
     */
    public function storeTreatment(Request $request, string $visitId)
    {
        // DEBUG: Log request data
        \Log::info('storeTreatment REQUEST:', $request->all());

        // Validate input (FASE 20.X: catalog is REQUIRED)
        $validated = $request->validate([
            'treatment_definition_id' => 'required|uuid|exists:treatment_definitions,id', // FASE 20.X: REQUIRED
            'tooth' => 'nullable|string|max:10',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        \Log::info('storeTreatment VALIDATED:', $validated);

        try {
            // Use ClinicalTreatmentService (CQRS write side)
            $treatment = $this->clinicalTreatmentService->addTreatmentToVisit($visitId, $validated);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // Get clinic_id from context
            $clinicId = app()->has('currentClinicId')
                ? app('currentClinicId')
                : \App\Models\Clinic::latest()->first()?->id ?? 1;

            // HTMX response: get fresh projection data
            // Use fresh() to bypass any model cache and get latest from DB
            $clinicalVisit = \App\Models\ClinicalVisit::where('id', $visitId)->first();

            if (!$clinicalVisit) {
                abort(404, 'Clinical visit projection not found');
            }

            // Load treatments fresh from DB
            $clinicalVisit->treatments = $this->clinicalTreatmentRepository->getTreatmentsForVisit($visitId);

            // FASE 21.1: Load active professionals for visit assignment
            $professionals = \App\Models\ClinicalProfessional::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            // Return treatments + OOB swap for visit header (syncs treatments_count)
            return view('workspace.patient.partials._visit_treatments_with_header', compact('clinicalVisit', 'professionals'));
        } catch (\DomainException $e) {
            \Log::error('storeTreatment DomainException: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update an existing treatment (FASE 20.4).
     *
     * Internal write endpoint - NOT part of public API v1.
     * Returns only the updated treatment (outerHTML swap) to preserve list order.
     */
    public function updateTreatment(Request $request, VisitTreatment $treatment)
    {
        // Validate input
        $validated = $request->validate([
            'type' => 'nullable|string|max:255',
            'tooth' => 'nullable|string|max:10',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            // Use ClinicalTreatmentService (CQRS write side)
            $writeTreatment = $this->clinicalTreatmentService->updateTreatment($treatment->id, $validated);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // HTMX response: return only the updated treatment (outerHTML swap)
            // This preserves the treatment's position in the list (no reordering)
            $treatmentProjection = \App\Models\ClinicalTreatment::find($treatment->id);

            if (!$treatmentProjection) {
                abort(404, 'Treatment projection not found');
            }

            $visitId = $treatment->visit_id;

            return view('workspace.patient.partials._visit_treatment_item', [
                'treatment' => $treatmentProjection,
                'visitId' => $visitId
            ]);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove a treatment from a visit (FASE 20.4).
     *
     * Internal write endpoint - NOT part of public API v1.
     *
     * FASE 20.6 bug fix: Returns OOB swap to update visit header
     * (syncs treatments_count so delete visit button enables correctly)
     */
    public function deleteTreatment(VisitTreatment $treatment)
    {
        try {
            $visitId = $treatment->visit_id;

            // Use ClinicalTreatmentService (CQRS write side)
            $this->clinicalTreatmentService->removeTreatmentFromVisit($treatment->id);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // Get clinic_id from context
            $clinicId = app()->has('currentClinicId')
                ? app('currentClinicId')
                : \App\Models\Clinic::latest()->first()?->id ?? 1;

            // HTMX response: get fresh projection data
            $clinicalVisit = \App\Models\ClinicalVisit::where('id', $visitId)->first();

            if (!$clinicalVisit) {
                abort(404, 'Clinical visit projection not found');
            }

            // Load treatments fresh from DB
            $clinicalVisit->treatments = $this->clinicalTreatmentRepository->getTreatmentsForVisit($visitId);

            // FASE 21.1: Load active professionals for visit assignment
            $professionals = \App\Models\ClinicalProfessional::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            // Return treatments + OOB swap for visit header (syncs treatments_count)
            return view('workspace.patient.partials._visit_treatments_with_header', compact('clinicalVisit', 'professionals'));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update an existing visit (FASE 20.6).
     *
     * Internal write endpoint - NOT part of public API v1.
     */
    public function updateVisit(Request $request, Visit $visit)
    {
        // Validate input (FASE 21.1: professional_id now references professionals table with UUID)
        $validated = $request->validate([
            'occurred_at' => 'nullable|date',
            'visit_type' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'professional_id' => 'nullable|uuid|exists:professionals,id',
        ]);

        try {
            $patientId = $visit->patient_id;
            $clinicId = $visit->clinic_id;

            // Use ClinicalVisitService (CQRS write side)
            $this->clinicalVisitService->updateVisit($visit->id, $validated);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // HTMX response: refresh visits partial
            $visitsPage = 1; // Show first page after update
            $visitsPaginator = $this->clinicalVisitRepository->getVisitsForPatientPaginated(
                $clinicId,
                $patientId,
                8,
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
            foreach ($clinicalVisits as $v) {
                $v->treatments = $this->clinicalTreatmentRepository->getTreatmentsForVisit($v->id);
            }

            // FASE 20.5: Load active treatment definitions for catalog selection
            $treatmentDefinitions = \App\Models\ClinicalTreatmentDefinition::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            // FASE 21.1: Load active professionals for visit assignment
            $professionals = \App\Models\ClinicalProfessional::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            return view('workspace.patient.partials._visits_content', compact(
                'clinicalVisits',
                'visitsMeta',
                'patientId',
                'treatmentDefinitions', // FASE 20.5
                'professionals' // FASE 21.1
            ));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove a visit (FASE 20.6).
     *
     * Internal write endpoint - NOT part of public API v1.
     * CRITICAL: Blocks deletion if visit has treatments.
     */
    public function deleteVisit(Visit $visit)
    {
        try {
            $patientId = $visit->patient_id;
            $clinicId = $visit->clinic_id;

            // Use ClinicalVisitService (CQRS write side)
            // This will throw DomainException if visit has treatments
            $this->clinicalVisitService->removeVisit($visit->id);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // HTMX response: refresh visits partial
            $visitsPage = 1;
            $visitsPaginator = $this->clinicalVisitRepository->getVisitsForPatientPaginated(
                $clinicId,
                $patientId,
                8,
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
            foreach ($clinicalVisits as $v) {
                $v->treatments = $this->clinicalTreatmentRepository->getTreatmentsForVisit($v->id);
            }

            // FASE 20.5: Load active treatment definitions for catalog selection
            $treatmentDefinitions = \App\Models\ClinicalTreatmentDefinition::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            // FASE 21.1: Load active professionals for visit assignment
            $professionals = \App\Models\ClinicalProfessional::forClinic($clinicId)
                ->active()
                ->alphabetical()
                ->get();

            return view('workspace.patient.partials._visits_content', compact(
                'clinicalVisits',
                'visitsMeta',
                'patientId',
                'treatmentDefinitions', // FASE 20.5
                'professionals' // FASE 21.1
            ));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
