{{-- FASE 20.4: Partial para un solo tratamiento (swap puntual en update) --}}
<li class="aura-treatment-item" id="treatment-{{ $treatment->id }}" style="display: flex; align-items: center; gap: 0.5rem;">
    {{-- Vista normal --}}
    <div id="treatment-view-{{ $treatment->id }}" style="flex: 1;">
        <span class="aura-treatment-type">{{ $treatment->type }}</span>
        @if($treatment->tooth)
            <span class="aura-treatment-tooth">(Pieza {{ $treatment->tooth }})</span>
        @endif
        @if($treatment->notes)
            <span class="aura-treatment-notes" style="width: auto; margin: 0; padding: 0; display: inline;"> — {{ $treatment->notes }}</span>
        @endif
        @if($treatment->amount)
            <span class="aura-treatment-amount"> · {{ number_format($treatment->amount, 2) }} €</span>
        @endif
    </div>

    {{-- Formulario de edición (oculto por defecto) --}}
    <form id="treatment-edit-{{ $treatment->id }}"
          style="display: none; flex: 1; gap: 0.25rem; align-items: center;"
          hx-patch="{{ route('workspace.treatments.update', ['treatment' => $treatment->id]) }}"
          hx-target="#treatment-{{ $treatment->id }}"
          hx-swap="outerHTML"
          data-base-amount="{{ $treatment->treatmentDefinition->default_price ?? '' }}">
        @csrf
        <input type="text" name="type" value="{{ $treatment->type }}"
               style="width: 8rem; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;"
               placeholder="Tipo">
        <input type="text"
               name="tooth"
               id="edit-tooth-{{ $treatment->id }}"
               value="{{ $treatment->tooth }}"
               class="edit-treatment-tooth"
               data-treatment-id="{{ $treatment->id }}"
               style="width: 3rem; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;"
               placeholder="Pieza">
        <input type="number"
               name="amount"
               id="edit-amount-{{ $treatment->id }}"
               value="{{ $treatment->amount }}"
               step="0.01"
               min="0"
               class="edit-treatment-amount"
               data-treatment-id="{{ $treatment->id }}"
               style="width: 5rem; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;"
               placeholder="€">
        <input type="text" name="notes" value="{{ $treatment->notes }}"
               style="flex: 1; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 2px; font-size: 0.875rem;"
               placeholder="Notas">
        <button type="submit" style="padding: 0.25rem 0.5rem; border: none; background: #0ea5e9; color: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">✓</button>
        <button type="button" onclick="toggleEditTreatment('{{ $treatment->id }}')" style="padding: 0.25rem 0.5rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">✕</button>
    </form>

    {{-- Acciones discretas --}}
    <div id="treatment-actions-{{ $treatment->id }}" style="display: flex; gap: 0.25rem;">
        <button onclick="toggleEditTreatment('{{ $treatment->id }}')"
                style="padding: 0.125rem 0.375rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem; color: #6b7280;">
            ✎
        </button>
        <button onclick="showDeleteConfirmation('{{ $treatment->id }}')"
                style="padding: 0.125rem 0.375rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem; color: #ef4444;">
            ×
        </button>
    </div>

    {{-- Confirmación inline de eliminación --}}
    <div id="treatment-delete-confirm-{{ $treatment->id }}" style="display: none; gap: 0.25rem; align-items: center;">
        <span style="font-size: 0.75rem; color: #ef4444;">¿Eliminar?</span>
        <button onclick="confirmDelete('{{ $treatment->id }}')"
                style="padding: 0.25rem 0.5rem; border: none; background: #ef4444; color: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">
            Sí, eliminar
        </button>
        <button onclick="cancelDelete('{{ $treatment->id }}')"
                style="padding: 0.25rem 0.5rem; border: 1px solid #d1d5db; background: white; border-radius: 2px; cursor: pointer; font-size: 0.75rem;">
            Cancelar
        </button>
    </div>

    {{-- Form oculto para DELETE --}}
    <form id="delete-form-{{ $treatment->id }}"
          style="display: none;"
          hx-delete="{{ route('workspace.treatments.delete', ['treatment' => $treatment->id]) }}"
          hx-target="#treatments-list-{{ $visitId }}"
          hx-swap="innerHTML">
        @csrf
    </form>
</li>

@once
<script>
// FASE 20.X: Auto-calculate amount for treatment editing (same logic as new treatment modal)
(function() {
    'use strict';

    const editAmountManuallyEdited = new Map();
    const editIsProgrammaticUpdate = new Map();

    function countTeeth(toothString) {
        if (!toothString || toothString.trim() === '') return 0;
        const teeth = toothString.split(',')
            .map(t => t.trim())
            .filter(t => t !== '');
        return teeth.length;
    }

    function calculateSuggestedAmount(baseAmount, teethCount) {
        const multiplier = Math.max(1, teethCount);
        return baseAmount * multiplier;
    }

    // Handler for tooth input in edit forms
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('edit-treatment-tooth')) {
            const treatmentId = e.target.getAttribute('data-treatment-id');
            const amountInput = document.getElementById('edit-amount-' + treatmentId);
            const form = document.getElementById('treatment-edit-' + treatmentId);

            if (!amountInput || !form) return;

            const baseAmount = parseFloat(form.getAttribute('data-base-amount'));

            // Only recalculate if we have a base amount and user hasn't manually edited
            if (!isNaN(baseAmount) && baseAmount > 0) {
                const teethCount = countTeeth(e.target.value);
                const suggestedAmount = calculateSuggestedAmount(baseAmount, teethCount);

                // Mark as programmatic to prevent marking as manual edit
                editIsProgrammaticUpdate.set(treatmentId, true);
                amountInput.value = suggestedAmount.toFixed(2);
                setTimeout(() => editIsProgrammaticUpdate.set(treatmentId, false), 0);

                // Reset manual edit flag
                editAmountManuallyEdited.set(treatmentId, false);
            }
        }

        // Handler for amount input - mark as manually edited
        if (e.target.classList.contains('edit-treatment-amount')) {
            const treatmentId = e.target.getAttribute('data-treatment-id');

            // Only mark as manual if this is a real user interaction (not programmatic)
            if (!editIsProgrammaticUpdate.get(treatmentId)) {
                editAmountManuallyEdited.set(treatmentId, true);
            }
        }
    });
})();
</script>
@endonce
