<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientController extends Controller
{
    private function getAllPatients()
    {
        return [
            ['id' => 1, 'name' => 'Ana García Ruiz', 'dni' => '12345678A', 'status' => 'Activo'],
            ['id' => 2, 'name' => 'Carlos Fernández López', 'dni' => '23456789B', 'status' => 'Activo'],
            ['id' => 3, 'name' => 'María Sánchez Torres', 'dni' => '34567890C', 'status' => 'Inactivo'],
            ['id' => 4, 'name' => 'Juan Martínez Díaz', 'dni' => '45678901D', 'status' => 'Activo'],
            ['id' => 5, 'name' => 'Laura Rodríguez Pérez', 'dni' => '56789012E', 'status' => 'Activo'],
            ['id' => 6, 'name' => 'David González Moreno', 'dni' => '67890123F', 'status' => 'Activo'],
            ['id' => 7, 'name' => 'Elena Jiménez Castro', 'dni' => '78901234G', 'status' => 'Inactivo'],
            ['id' => 8, 'name' => 'Pablo Romero Navarro', 'dni' => '89012345H', 'status' => 'Activo'],
            ['id' => 9, 'name' => 'Sofía Muñoz Vega', 'dni' => '90123456I', 'status' => 'Activo'],
            ['id' => 10, 'name' => 'Miguel Álvarez Ortiz', 'dni' => '01234567J', 'status' => 'Activo'],
            ['id' => 11, 'name' => 'Carmen Ruiz Blanco', 'dni' => '11234567K', 'status' => 'Activo'],
            ['id' => 12, 'name' => 'José Torres Vega', 'dni' => '12234567L', 'status' => 'Inactivo'],
            ['id' => 13, 'name' => 'Isabel Morales Cruz', 'dni' => '13234567M', 'status' => 'Activo'],
            ['id' => 14, 'name' => 'Antonio Silva Ramos', 'dni' => '14234567N', 'status' => 'Activo'],
            ['id' => 15, 'name' => 'Lucía Herrera Campos', 'dni' => '15234567O', 'status' => 'Activo'],
            ['id' => 16, 'name' => 'Francisco Ortiz Navarro', 'dni' => '16234567P', 'status' => 'Inactivo'],
            ['id' => 17, 'name' => 'Raquel Castro Reyes', 'dni' => '17234567Q', 'status' => 'Activo'],
            ['id' => 18, 'name' => 'Manuel Delgado Prieto', 'dni' => '18234567R', 'status' => 'Activo'],
        ];
    }

    public function index(Request $request)
    {
        $allPatients = $this->getAllPatients();
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
