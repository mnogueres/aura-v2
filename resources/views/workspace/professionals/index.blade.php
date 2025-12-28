@extends('layouts.aura')

@section('header', 'Profesionales')

@section('content')

<div class="aura-workspace-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
        Gestiona los profesionales de tu clínica
    </p>

    <button
        onclick="document.getElementById('new-professional-modal').style.display = 'flex'"
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
        + Nuevo profesional
    </button>
</div>

<div x-data="{
        search: '',
        professionals: {{ json_encode($professionals->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'role' => $p->role,
                'active' => $p->active,
                'status' => $p->active ? 'Activo' : 'Inactivo',
            ];
        })->values()) }},
        get filteredProfessionals() {
            if (this.search === '') return this.professionals;

            const query = this.search.toLowerCase();
            return this.professionals.filter(professional =>
                professional.name.toLowerCase().includes(query) ||
                this.getRoleLabel(professional.role).toLowerCase().includes(query)
            );
        },
        getRoleLabel(role) {
            const labels = {
                'dentist': 'Odontólogo/a',
                'hygienist': 'Higienista',
                'assistant': 'Asistente',
                'other': 'Otro'
            };
            return labels[role] || role;
        }
    }">
    <!-- Buscador -->
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre o rol..."
            x-model="search"
            autocomplete="off">
    </div>

    <!-- Listado -->
    <div class="aura-patient-list">
        <template x-for="professional in filteredProfessionals" :key="professional.id">
            <div
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                style="cursor: default;">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="professional.name"></h3>
                    <span class="aura-patient-dni" x-text="getRoleLabel(professional.role)"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="professional.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="professional.status"></span>
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

{{-- New Professional Modal --}}
@include('workspace.professionals.partials._new_professional_modal', ['clinicId' => $clinicId])

@endsection
