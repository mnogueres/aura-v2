{{-- FASE 20.7 BLOQUE 3: New Treatment Modal (Aura style) --}}
{{-- IMPORTANTE: Este modal y su estilo son PROVISIONALES --}}
{{-- NO establecen el sistema visual definitivo de Aura --}}

<div id="new-treatment-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #1e293b;">Nuevo tratamiento</h2>

        <form
            id="new-treatment-form"
            hx-post="{{ route('workspace.treatments.store') }}"
            hx-target="#treatments-content"
            hx-swap="outerHTML"
        >
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="treatment-name" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #334155;">
                    Nombre del tratamiento <span style="color: #e11d48;">*</span>
                </label>
                <input
                    type="text"
                    id="treatment-name"
                    name="name"
                    required
                    placeholder="Empaste, Endodoncia, Limpieza..."
                    style="width: 100%; padding: 0.625rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="treatment-price" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #334155;">
                    Precio de referencia (€)
                </label>
                <input
                    type="number"
                    id="treatment-price"
                    name="default_price"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    style="width: 100%; padding: 0.625rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;"
                >
                <p style="margin: 0.375rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                    Opcional. Precio sugerido que puede ajustarse en cada visita.
                </p>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button
                    type="button"
                    onclick="document.getElementById('new-treatment-modal').style.display = 'none'"
                    style="
                        padding: 0.625rem 1.25rem;
                        border: 1px solid #cbd5e1;
                        background: white;
                        color: #64748b;
                        border-radius: 4px;
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s;
                    "
                    onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#cbd5e1';"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    style="
                        padding: 0.625rem 1.25rem;
                        border: none;
                        background: #0ea5e9;
                        color: white;
                        border-radius: 4px;
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: background 0.2s;
                    "
                    onmouseover="this.style.background='#0284c7';"
                    onmouseout="this.style.background='#0ea5e9';"
                >
                    Crear tratamiento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const formId = 'new-treatment-form';
    const modalId = 'new-treatment-modal';

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
