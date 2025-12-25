@extends('layouts.aura')

@section('header', 'Pacientes')

@section('content')
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
    <div class="aura-search">
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
            <div
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
                    class="aura-patient-status"
                    :class="patient.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="patient.status"></span>
            </div>
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