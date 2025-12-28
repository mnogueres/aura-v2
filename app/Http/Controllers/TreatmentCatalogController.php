<?php

namespace App\Http\Controllers;

use App\Models\ClinicalTreatmentDefinition;
use App\Models\TreatmentDefinition;
use App\Services\ClinicalTreatmentCatalogService;
use App\Services\OutboxEventConsumer;
use Illuminate\Http\Request;

/**
 * FASE 20.7: Treatment Catalog management in Workspace
 *
 * Workspace UI for managing the clinic's treatment catalog.
 * Uses existing domain services from FASE 20.5.
 *
 * Responsibilities:
 * - Display treatment catalog (read model)
 * - CRUD operations via domain service
 * - HTMX partial updates
 *
 * NOT responsible for:
 * - Domain logic (handled by ClinicalTreatmentCatalogService)
 * - Creating visit treatments (handled by PatientWorkspaceController)
 */
class TreatmentCatalogController extends Controller
{
    public function __construct(
        private readonly ClinicalTreatmentCatalogService $catalogService,
        private readonly OutboxEventConsumer $outboxConsumer
    ) {
    }

    /**
     * Display treatment catalog index (FASE 20.7 BLOQUE 2).
     *
     * Supports dynamic search via HTMX.
     */
    public function index(Request $request)
    {
        // Get clinic_id from context (temporal para demo)
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Build query
        $query = ClinicalTreatmentDefinition::forClinic($clinicId);

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->get('search'))) {
            $searchTerm = $request->get('search');
            $query->where('name', 'LIKE', '%' . $searchTerm . '%');
        }

        // Fetch treatment definitions (read model) - alphabetically ordered with canonical pagination (8 per page)
        $treatments = $query->alphabetical()->paginate(8);

        // Calculate usage count for each treatment (for conditional delete button)
        $treatments->each(function ($treatment) {
            $treatment->usage_count = \App\Models\VisitTreatment::withTrashed()
                ->where('treatment_definition_id', $treatment->id)
                ->count();
        });

        // HTMX partial request (search or explicit partial)
        if ($request->has('partial') && $request->get('partial') === 'list') {
            return view('workspace.treatments.partials._treatments_list', compact('treatments'));
        }

        // HTMX search request (returns only list)
        if ($request->header('HX-Request')) {
            return view('workspace.treatments.partials._treatments_list', compact('treatments'));
        }

        return view('workspace.treatments.index', compact('treatments'));
    }

    /**
     * Store a new treatment definition (FASE 20.7 BLOQUE 3 + FASE 20.X quick create).
     *
     * Supports both HTMX (from catalog page) and JSON (from quick create).
     */
    public function store(Request $request)
    {
        // Get clinic_id from context
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
        ]);

        try {
            // Use domain service (CQRS write side)
            $definition = $this->catalogService->createTreatmentDefinition([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'default_price' => $validated['default_price'] ?? null,
            ]);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // FASE 20.X: JSON response for quick create from visit modal
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                $treatment = ClinicalTreatmentDefinition::find($definition->id);
                return response()->json([
                    'success' => true,
                    'treatment' => [
                        'id' => $treatment->id,
                        'name' => $treatment->name,
                        'default_price' => $treatment->default_price,
                    ]
                ]);
            }

            // HTMX response: refresh treatments content (FASE 21.3 - canonical response)
            $treatments = ClinicalTreatmentDefinition::forClinic($clinicId)
                ->alphabetical()
                ->paginate(8);

            return view('workspace.treatments.partials._treatments_content', compact('treatments'));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage(), 'success' => false], 422);
        }
    }

    /**
     * Update an existing treatment definition (FASE 20.7 BLOQUE 3).
     */
    public function update(Request $request, TreatmentDefinition $treatmentDefinition)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
        ]);

        try {
            // Use domain service (CQRS write side)
            $this->catalogService->updateTreatmentDefinition($treatmentDefinition->id, $validated);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // Get clinic_id from context
            $clinicId = app()->has('currentClinicId')
                ? app('currentClinicId')
                : \App\Models\Clinic::latest()->first()?->id ?? 1;

            // HTMX response: refresh treatments content (FASE 21.3 - canonical response)
            $treatments = ClinicalTreatmentDefinition::forClinic($clinicId)
                ->alphabetical()
                ->paginate(8);

            return view('workspace.treatments.partials._treatments_content', compact('treatments'));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Toggle active status of a treatment definition (FASE 20.7 BLOQUE 4).
     */
    public function toggleActive(TreatmentDefinition $treatmentDefinition)
    {
        try {
            // Toggle: if active, deactivate; if inactive, reactivate
            if ($treatmentDefinition->active) {
                // Deactivate using domain service
                $this->catalogService->deactivateTreatmentDefinition($treatmentDefinition->id);
            } else {
                // Reactivate: update active = true
                $this->catalogService->updateTreatmentDefinition($treatmentDefinition->id, [
                    'active' => true,
                ]);
            }

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // Get clinic_id from context
            $clinicId = app()->has('currentClinicId')
                ? app('currentClinicId')
                : \App\Models\Clinic::latest()->first()?->id ?? 1;

            // HTMX response: refresh treatments content (FASE 21.3 - canonical response)
            $treatments = ClinicalTreatmentDefinition::forClinic($clinicId)
                ->alphabetical()
                ->paginate(8);

            return view('workspace.treatments.partials._treatments_content', compact('treatments'));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a treatment definition (FASE 20.7).
     *
     * CRITICAL: Can ONLY delete if NEVER used in any visit.
     * If used, domain service will throw exception.
     */
    public function destroy(TreatmentDefinition $treatmentDefinition)
    {
        try {
            // Use domain service to delete (includes usage validation)
            $this->catalogService->deleteTreatmentDefinition($treatmentDefinition->id);

            // Process outbox events immediately for instant projection
            $this->outboxConsumer->processPendingEvents();

            // Get clinic_id from context
            $clinicId = app()->has('currentClinicId')
                ? app('currentClinicId')
                : \App\Models\Clinic::latest()->first()?->id ?? 1;

            // HTMX response: refresh treatments content (FASE 21.3 - canonical response)
            $treatments = ClinicalTreatmentDefinition::forClinic($clinicId)
                ->alphabetical()
                ->paginate(8);

            return view('workspace.treatments.partials._treatments_content', compact('treatments'));
        } catch (\DomainException $e) {
            // Return error if treatment has usage
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
