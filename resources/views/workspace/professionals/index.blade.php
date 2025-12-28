@extends('layouts.aura')

@section('header', 'Profesionales')

@section('content')
{{-- FASE 21.0/21.1: Professional Catalog Workspace with Dynamic Search --}}

<div class="aura-workspace-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
        Gestiona el catálogo de profesionales de tu clínica
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
                'user_id' => $p->user_id,
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

    {{-- Search Input (FASE 21.1 - BLOQUE 3) --}}
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre o rol..."
            x-model="search"
            autocomplete="off">
    </div>

    {{-- Professionals List (HTMX swap target) --}}
    <div id="professionals-list">
        @include('workspace.professionals.partials._professionals_list', ['professionals' => $professionals])
    </div>
</div>

{{-- New Professional Modal --}}
@include('workspace.professionals.partials._new_professional_modal', ['clinicId' => $clinicId])

@endsection
