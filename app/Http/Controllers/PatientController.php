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
}
