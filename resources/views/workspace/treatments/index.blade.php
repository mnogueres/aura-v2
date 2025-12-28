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

@include('workspace.treatments.partials._treatments_content', ['treatments' => $treatments])

<!-- Status bar FUERA de la cápsula -->
<div class="aura-statusbar">
    <div class="aura-pagination">
        <a
            href="{{ $treatments->previousPageUrl() ?? '#' }}"
            class="aura-pagination-btn {{ $treatments->onFirstPage() ? 'disabled' : '' }}">
            Anterior
        </a>

        <div class="aura-pagination-pages">
            @for ($i = 1; $i <= $treatments->lastPage(); $i++)
                <a
                href="{{ $treatments->url($i) }}"
                class="aura-pagination-page {{ $i === $treatments->currentPage() ? 'active' : '' }}">
                {{ $i }}
                </a>
            @endfor
        </div>

        <a
            href="{{ $treatments->nextPageUrl() ?? '#' }}"
            class="aura-pagination-btn {{ !$treatments->hasMorePages() ? 'disabled' : '' }}">
            Siguiente
        </a>
    </div>
</div>

{{-- New Treatment Modal --}}
@include('workspace.treatments.partials._new_treatment_modal')

@endsection
