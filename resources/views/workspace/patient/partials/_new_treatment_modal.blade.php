{{-- FASE 20.3: Modal provisional para añadir tratamiento --}}
{{-- IMPORTANTE: Este modal y su estilo son PROVISIONALES --}}
{{-- NO establecen el sistema visual definitivo de Aura --}}

<div id="new-treatment-modal-{{ $visitId }}" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 4px; max-width: 500px; width: 90%;">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem;">Añadir tratamiento</h2>

        <form
            id="treatment-form-{{ $visitId }}"
            hx-post="{{ route('workspace.visit.treatments.store', ['visit' => $visitId]) }}"
            hx-target="#treatments-list-{{ $visitId }}"
            hx-swap="innerHTML"
        >
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="type-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Tipo de tratamiento <span style="color: #e11d48;">*</span>
                </label>
                <input
                    type="text"
                    id="type-{{ $visitId }}"
                    name="type"
                    required
                    placeholder="Empaste, Endodoncia, Limpieza..."
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;"
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="tooth-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Pieza dental
                </label>
                <input
                    type="text"
                    id="tooth-{{ $visitId }}"
                    name="tooth"
                    placeholder="16, 21, 36..."
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;"
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="amount-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Importe (€)
                </label>
                <input
                    type="number"
                    id="amount-{{ $visitId }}"
                    name="amount"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="notes-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Notas
                </label>
                <textarea
                    id="notes-{{ $visitId }}"
                    name="notes"
                    rows="3"
                    placeholder="Observaciones sobre el tratamiento..."
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"
                ></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button
                    type="button"
                    onclick="document.getElementById('new-treatment-modal-{{ $visitId }}').style.display = 'none'"
                    style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    style="padding: 0.5rem 1rem; border: none; background: #0ea5e9; color: white; border-radius: 4px; cursor: pointer;"
                >
                    Añadir tratamiento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const formId = 'treatment-form-{{ $visitId }}';
    const modalId = 'new-treatment-modal-{{ $visitId }}';

    // Listen for afterRequest on the form itself (fires after HTTP response)
    document.body.addEventListener('htmx:afterRequest', function(event) {
        // Check if the event came from our specific form and was successful
        if (event.detail.elt && event.detail.elt.id === formId && event.detail.successful) {
            const modal = document.getElementById(modalId);
            const form = document.getElementById(formId);

            if (modal && form) {
                // Small delay to ensure swap completes visually before closing
                setTimeout(function() {
                    modal.style.display = 'none';
                    form.reset();
                }, 100);
            }
        }
    });
})();
</script>
