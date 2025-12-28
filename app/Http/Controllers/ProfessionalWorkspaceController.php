<?php

namespace App\Http\Controllers;

use App\Repositories\ClinicalProfessionalRepository;
use App\Services\ClinicalProfessionalService;
use App\Services\OutboxEventConsumer;
use Illuminate\Http\Request;

/**
 * ProfessionalWorkspaceController - UI for professional catalog (FASE 21.0)
 *
 * Handles CRUD operations for clinical professionals.
 * Professionals are independent of system users.
 */
class ProfessionalWorkspaceController extends Controller
{
    public function __construct(
        private readonly ClinicalProfessionalRepository $repository,
        private readonly ClinicalProfessionalService $service,
        private readonly OutboxEventConsumer $outboxConsumer
    ) {
    }

    /**
     * Show professional catalog index page.
     */
    public function index(Request $request)
    {
        // Get clinic_id (temporal para demo - usar el último creado)
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Fetch all professionals (active and inactive) for catalog management
        $professionals = $this->repository->getAllProfessionals($clinicId);

        return view('workspace.professionals.index', [
            'professionals' => $professionals,
            'clinicId' => $clinicId,
        ]);
    }

    /**
     * Store a new professional.
     */
    public function store(Request $request)
    {
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:dentist,hygienist,assistant,other',
        ]);

        try {
            $professional = $this->service->createProfessional([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'role' => $validated['role'],
                'active' => true,
            ]);

            // Process outbox events immediately
            $this->outboxConsumer->processPendingEvents();

            // Return updated list
            $professionals = $this->repository->getAllProfessionals($clinicId);

            return view('workspace.professionals.partials._professionals_list', [
                'professionals' => $professionals,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update an existing professional.
     */
    public function update(Request $request, Professional $professional)
    {
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'role' => 'sometimes|required|string|in:dentist,hygienist,assistant,other',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $this->service->updateProfessional($professional->id, $validated);

            // Process outbox events immediately
            $this->outboxConsumer->processPendingEvents();

            // Return updated list
            $professionals = $this->repository->getAllProfessionals($clinicId);

            return view('workspace.professionals.partials._professionals_list', [
                'professionals' => $professionals,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Deactivate a professional.
     */
    public function deactivate(Professional $professional)
    {
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        try {
            $this->service->deactivateProfessional($professional->id);

            // Process outbox events immediately
            $this->outboxConsumer->processPendingEvents();

            // Return updated list
            $professionals = $this->repository->getAllProfessionals($clinicId);

            return view('workspace.professionals.partials._professionals_list', [
                'professionals' => $professionals,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Activate a professional (reverse of deactivate).
     */
    public function activate(Professional $professional)
    {
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        try {
            // Use updateProfessional to set active = true
            $this->service->updateProfessional($professional->id, ['active' => true]);

            // Process outbox events immediately
            $this->outboxConsumer->processPendingEvents();

            // Return updated list
            $professionals = $this->repository->getAllProfessionals($clinicId);

            return view('workspace.professionals.partials._professionals_list', [
                'professionals' => $professionals,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
