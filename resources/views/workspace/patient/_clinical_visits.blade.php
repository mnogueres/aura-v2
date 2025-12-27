{{-- Historial de Visitas Clínicas (FASE 17) --}}
<div class="aura-timeline-block">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 class="aura-block-title" style="margin: 0;">Historial de Visitas</h2>

        {{-- FASE 20.2: Botón PROVISIONAL para crear visita --}}
        {{-- IMPORTANTE: Este botón y su estilo son PROVISIONALES --}}
        {{-- NO establecen el sistema visual definitivo de Aura --}}
        <button
            onclick="document.getElementById('new-visit-modal').style.display = 'flex'"
            style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 0.875rem;"
        >
            + Nueva visita
        </button>
    </div>

    <div id="visits-content">
        @include('workspace.patient.partials._visits_content')
    </div>
</div>

{{-- Include modal --}}
@include('workspace.patient._new_visit_modal')
