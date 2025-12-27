{{-- HTMX OOB target: updates when treatments change (FASE 20.6 bug fix) --}}
<summary id="visit-header-{{ $visit->id }}" class="aura-visit-summary" style="display: flex; align-items: center; gap: 0.5rem;" {{ ($oob ?? false) ? 'hx-swap-oob="true"' : '' }}>
    {{-- Vista normal --}}
    <div id="visit-view-{{ $visit->id }}" class="aura-visit-header" style="flex: 1;">
        <span class="aura-visit-date">
            {{ $visit->occurred_at->format('d M Y, H:i') }}
        </span>
        <span class="aura-visit-professional">
            Visita con {{ $visit->professional?->name ?? 'Profesional no asignado' }}
        </span>
        @if($visit->treatments_count > 0)
            <span class="aura-visit-badge">
                {{ $visit->treatments_count }} {{ $visit->treatments_count === 1 ? 'tratamiento' : 'tratamientos' }}
            </span>
        @endif
        @if($visit->summary)
            <span class="aura-visit-summary-text">{{ $visit->summary }}</span>
        @endif
    </div>

    {{-- Formulario de edición inline (oculto por defecto) --}}
    <form id="visit-edit-{{ $visit->id }}"
          style="display: none; flex: 1; gap: 0.25rem; align-items: center; flex-wrap: wrap;"
          hx-patch="{{ route('workspace.visits.update', ['visit' => $visit->id]) }}"
          hx-target="#visits-content"
          hx-swap="innerHTML">
        @csrf
        <input type="datetime-local" name="occurred_at"
               value="{{ $visit->occurred_at->format('Y-m-d\TH:i') }}"
               style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;">
        <input type="text" name="visit_type" value="{{ $visit->visit_type }}"
               style="width: 8rem; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;"
               placeholder="Tipo">
        <input type="text" name="summary" value="{{ $visit->summary }}"
               style="flex: 1; min-width: 10rem; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;"
               placeholder="Resumen">
        <button type="submit" style="padding: 0.25rem 0.5rem; border: none; background: #0ea5e9; color: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">✓</button>
        <button type="button" onclick="toggleEditVisit('{{ $visit->id }}')" style="padding: 0.25rem 0.5rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">✕</button>
    </form>

    {{-- Acciones discretas --}}
    <div id="visit-actions-{{ $visit->id }}" style="display: flex; gap: 0.25rem; align-items: center;">
        <button onclick="toggleEditVisit('{{ $visit->id }}')"
                style="padding: 0.125rem 0.375rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem; color: #6b7280;">
            ✎
        </button>
        <button onclick="showDeleteVisitConfirmation('{{ $visit->id }}', {{ $visit->treatments_count }})"
                style="padding: 0.125rem 0.375rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem; color: #ef4444;">
            ×
        </button>
    </div>

    {{-- Confirmación inline de eliminación --}}
    <div id="visit-delete-confirm-{{ $visit->id }}" style="display: none; gap: 0.25rem; align-items: center;">
        <span style="font-size: 0.75rem; color: #ef4444;">¿Eliminar?</span>
        <button onclick="confirmDeleteVisit('{{ $visit->id }}')"
                style="padding: 0.25rem 0.5rem; border: none; background: #ef4444; color: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">
            Sí, eliminar
        </button>
        <button onclick="cancelDeleteVisit('{{ $visit->id }}')"
                style="padding: 0.25rem 0.5rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">
            Cancelar
        </button>
    </div>

    {{-- Mensaje de bloqueo si tiene tratamientos --}}
    <div id="visit-delete-blocked-{{ $visit->id }}" style="display: none; gap: 0.25rem; align-items: center;">
        <span style="font-size: 0.75rem; color: #ef4444; font-weight: 500;">No se puede eliminar una visita con tratamientos</span>
        <button onclick="cancelDeleteVisit('{{ $visit->id }}')"
                style="padding: 0.25rem 0.5rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">
            Cerrar
        </button>
    </div>

    {{-- Form oculto para DELETE --}}
    <form id="delete-visit-form-{{ $visit->id }}"
          style="display: none;"
          hx-delete="{{ route('workspace.visits.delete', ['visit' => $visit->id]) }}"
          hx-target="#visits-content"
          hx-swap="innerHTML">
        @csrf
    </form>
</summary>
