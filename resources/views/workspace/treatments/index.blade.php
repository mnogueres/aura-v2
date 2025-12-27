@extends('layouts.aura')

@section('header', 'Tratamientos')

@section('content')
{{-- FASE 20.7: Treatment Catalog Workspace --}}

<div class="aura-workspace-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
        Gestiona el catálogo de tratamientos de tu clínica
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

{{-- Dynamic Search (HTMX) --}}
<div style="margin-bottom: 1.5rem;">
    <input
        type="text"
        name="search"
        placeholder="Buscar tratamiento…"
        autocomplete="off"
        hx-get="{{ route('workspace.treatments.index') }}"
        hx-trigger="keyup changed delay:300ms"
        hx-target="#treatments-list"
        hx-push-url="false"
        hx-include="[name='search']"
        style="
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        "
        onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)';"
        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
    >
</div>

{{-- Treatments List (HTMX swap target) --}}
<div id="treatments-list">
    @include('workspace.treatments.partials._treatments_list', ['treatments' => $treatments])
</div>

{{-- New Treatment Modal (BLOQUE 3) --}}
@include('workspace.treatments.partials._new_treatment_modal')

@endsection
