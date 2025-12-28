@extends('layouts.aura')

@section('header', 'Tratamientos')

@section('content')

<div class="aura-workspace-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
        Gestiona los tratamientos de tu clínica
    </p>

    <button
        onclick="document.getElementById('new-treatment-modal').style.display = 'flex'"
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
        + Nuevo tratamiento
    </button>
</div>

<div x-data="{
        search: '',
        treatments: {{ json_encode($treatments->map(function($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'default_price' => $t->default_price,
                'active' => $t->active,
                'status' => $t->active ? 'Activo' : 'Inactivo',
                'price_label' => $t->default_price ? 'Precio ref: ' . number_format($t->default_price, 2) . '€' : 'Sin precio de referencia',
            ];
        })->values()) }},
        get filteredTreatments() {
            if (this.search === '') return this.treatments;

            const query = this.search.toLowerCase();
            return this.treatments.filter(treatment =>
                treatment.name.toLowerCase().includes(query)
            );
        }
    }">
    <!-- Buscador -->
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre..."
            x-model="search"
            autocomplete="off">
    </div>

    <!-- Listado -->
    <div class="aura-patient-list">
        <template x-for="treatment in filteredTreatments" :key="treatment.id">
            <div
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                style="cursor: default;">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="treatment.name"></h3>
                    <span class="aura-patient-dni" x-text="treatment.price_label"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="treatment.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="treatment.status"></span>
            </div>
        </template>
    </div>
</div>

<!-- Status bar FUERA de la cápsula -->
<div class="aura-statusbar">
    <div class="aura-pagination">
        <a href="#" class="aura-pagination-btn disabled">
            Anterior
        </a>

        <div class="aura-pagination-pages">
            <a href="#" class="aura-pagination-page active">
                1
            </a>
        </div>

        <a href="#" class="aura-pagination-btn disabled">
            Siguiente
        </a>
    </div>
</div>

{{-- New Treatment Modal --}}
@include('workspace.treatments.partials._new_treatment_modal')

@endsection
