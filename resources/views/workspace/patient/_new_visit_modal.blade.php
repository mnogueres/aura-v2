{{-- FASE 20.2: Modal provisional para crear visita --}}
{{-- IMPORTANTE: Este modal y su estilo son PROVISIONALES --}}
{{-- NO establecen el sistema visual definitivo de Aura --}}

<div id="new-visit-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 4px; max-width: 500px; width: 90%;">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem;">Nueva visita</h2>

        <form
            hx-post="{{ route('workspace.patient.visits.store', ['patient' => $patientId]) }}"
            hx-target="#visits-content"
            hx-swap="innerHTML"
            hx-on::after-request="if(event.detail.successful) { document.getElementById('new-visit-modal').style.display = 'none'; this.reset(); }"
        >
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="occurred_at" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Fecha y hora <span style="color: #e11d48;">*</span>
                </label>
                <input
                    type="datetime-local"
                    id="occurred_at"
                    name="occurred_at"
                    required
                    value="{{ now()->format('Y-m-d\TH:i') }}"
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;"
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="visit_type" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Tipo de visita
                </label>
                <input
                    type="text"
                    id="visit_type"
                    name="visit_type"
                    placeholder="Primera visita, Revisión, Urgencia..."
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;"
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="summary" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Resumen
                </label>
                <textarea
                    id="summary"
                    name="summary"
                    rows="3"
                    placeholder="Motivo de consulta, observaciones..."
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"
                ></textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="professional_id" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Profesional
                </label>
                <select
                    id="professional_id"
                    name="professional_id"
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;"
                >
                    <option value="">Sin asignar</option>
                    {{-- En fase futura: listar profesionales reales --}}
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button
                    type="button"
                    onclick="document.getElementById('new-visit-modal').style.display = 'none'"
                    style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    style="padding: 0.5rem 1rem; border: none; background: #0ea5e9; color: white; border-radius: 4px; cursor: pointer;"
                >
                    Crear visita
                </button>
            </div>
        </form>
    </div>
</div>
