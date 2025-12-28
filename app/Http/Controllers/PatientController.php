<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        // Obtener clinic_id (temporal para demo - usar el último creado)
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Fetch real patients from database (FASE 19 - Live Product)
        $allPatients = Patient::where('clinic_id', $clinicId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'dni' => $patient->dni,
                    'status' => 'Activo', // TODO: Implement real status in future phases
                ];
            })
            ->toArray();

        $perPage = 8;
        $currentPage = $request->get('page', 1);

        $total = count($allPatients);
        $totalPages = ceil($total / $perPage);
        $offset = ($currentPage - 1) * $perPage;

        $patients = array_slice($allPatients, $offset, $perPage);

        return view('patients.index', [
            'patients' => $patients,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    /**
     * Store a new patient (FASE 21.3 - canonical HTMX pattern).
     */
    public function store(Request $request)
    {
        // Get clinic_id from context
        $clinicId = app()->has('currentClinicId')
            ? app('currentClinicId')
            : \App\Models\Clinic::latest()->first()?->id ?? 1;

        // Validate input
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dni' => 'required|string|max:20',
        ]);

        try {
            // Create patient
            Patient::create([
                'clinic_id' => $clinicId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'dni' => $validated['dni'],
            ]);

            // Return updated content (FASE 21.3 - canonical HTMX response)
            $allPatients = Patient::where('clinic_id', $clinicId)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(function ($patient) {
                    return [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'dni' => $patient->dni,
                        'status' => 'Activo',
                    ];
                })
                ->toArray();

            // Get first page (8 items)
            $patients = array_slice($allPatients, 0, 8);

            return view('patients.partials._patients_content', compact('patients'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
