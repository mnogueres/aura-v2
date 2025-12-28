@extends('layouts.aura')

@section('header', 'Pacientes')

@section('content')

<div class="aura-workspace-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
        Gestiona los pacientes de tu clínica
    </p>

    <button
        onclick="alert('Funcionalidad próximamente disponible')"
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

<div x-data="{
        search: '',
        patients: {{ json_encode($patients) }},
        get filteredPatients() {
            if (this.search === '') return this.patients;

            const query = this.search.toLowerCase();
            return this.patients.filter(patient =>
                patient.name.toLowerCase().includes(query) ||
                patient.dni.toLowerCase().includes(query)
            );
        }
    }">
    <!-- Buscador -->
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre o DNI..."
            x-model="search"
            autocomplete="off">
    </div>

    <!-- Listado -->
    <div class="aura-patient-list">
        <template x-for="patient in filteredPatients" :key="patient.id">
            <a
                :href="`/workspace/patients/${patient.id}`"
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="patient.name"></h3>
                    <span class="aura-patient-dni" x-text="patient.dni"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="patient.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="patient.status"></span>
            </a>
        </template>
    </div>
</div>

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
@endsection