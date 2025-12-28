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

@include('workspace.professionals.partials._professionals_content', ['professionals' => $professionals])

<!-- Status bar FUERA de la cápsula -->
<div class="aura-statusbar">
    <div class="aura-pagination">
        <a
            href="{{ $professionals->previousPageUrl() ?? '#' }}"
            class="aura-pagination-btn {{ $professionals->onFirstPage() ? 'disabled' : '' }}">
            Anterior
        </a>

        <div class="aura-pagination-pages">
            @for ($i = 1; $i <= $professionals->lastPage(); $i++)
                <a
                href="{{ $professionals->url($i) }}"
                class="aura-pagination-page {{ $i === $professionals->currentPage() ? 'active' : '' }}">
                {{ $i }}
                </a>
            @endfor
        </div>

        <a
            href="{{ $professionals->nextPageUrl() ?? '#' }}"
            class="aura-pagination-btn {{ !$professionals->hasMorePages() ? 'disabled' : '' }}">
            Siguiente
        </a>
    </div>
</div>

{{-- New Professional Modal --}}
@include('workspace.professionals.partials._new_professional_modal', ['clinicId' => $clinicId])

{{-- Edit Professional Modal --}}
@include('workspace.professionals.partials._edit_professional_modal')

@endsection
