@extends('layouts.aura')

@section('header', 'Pacientes')

@section('content')

<div class="aura-workspace-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
        Gestiona los pacientes de tu clínica
    </p>

    <button
        onclick="document.getElementById('new-patient-modal').style.display = 'flex'"
        style="
            padding: 0.5rem 1rem;
            background: #0ea5e9;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        "
        onmouseover="this.style.background='#0284c7'"
        onmouseout="this.style.background='#0ea5e9'"
    >
        + Nuevo paciente
    </button>
</div>

@include('patients.partials._patients_content', ['patients' => $patients])

<!-- Status bar FUERA de la cápsula -->
<div class="aura-statusbar">
    <div class="aura-pagination">
        <a
            href="{{ $currentPage > 1 ? '?page=' . ($currentPage - 1) : '#' }}"
            class="aura-pagination-btn {{ $currentPage <= 1 ? 'disabled' : '' }}">
            Anterior
        </a>

        <div class="aura-pagination-pages">
            @for ($i = 1; $i <= $totalPages; $i++)
                <a
                href="?page={{ $i }}"
                class="aura-pagination-page {{ $i === $currentPage ? 'active' : '' }}">
                {{ $i }}
                </a>
                @endfor
        </div>

        <a
            href="{{ $currentPage < $totalPages ? '?page=' . ($currentPage + 1) : '#' }}"
            class="aura-pagination-btn {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
            Siguiente
        </a>
    </div>
</div>

{{-- New Patient Modal --}}
@include('patients.partials._new_patient_modal')

{{-- Edit Patient Modal --}}
@include('patients.partials._edit_patient_modal')

@endsection