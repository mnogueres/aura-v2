{{-- FASE 20.X: Modal para añadir tratamiento desde catálogo OBLIGATORIO --}}
{{-- Entrada manual eliminada - catálogo es la única fuente de verdad --}}

<div id="new-treatment-modal-{{ $visitId }}" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 4px; max-width: 500px; width: 90%;">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem;">Añadir tratamiento</h2>

        <form
            id="treatment-form-{{ $visitId }}"
            hx-post="{{ route('workspace.visit.treatments.store', ['visit' => $visitId]) }}"
            hx-target="#treatments-list-{{ $visitId }}"
            hx-swap="innerHTML"
            hx-on::after-request="if(event.detail.successful) { document.getElementById('new-treatment-modal-{{ $visitId }}').style.display = 'none'; this.reset(); }"
        >
            @csrf

            {{-- FASE 20.X: Catalog selection REQUIRED (no manual entry) --}}
            <div style="margin-bottom: 1rem;">
                <label for="catalog-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">
                    Tratamiento <span style="color: #e11d48;">*</span>
                </label>
                <select
                    id="catalog-{{ $visitId }}"
                    name="treatment_definition_id"
                    required
                    class="treatment-catalog-select"
                    data-amount-target="amount-{{ $visitId }}"
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 0.5rem;"
                >
                    <option value="">-- Seleccionar tratamiento --</option>
                    @foreach($treatmentDefinitions as $definition)
                        <option
                            value="{{ $definition->id }}"
                            data-name="{{ $definition->name }}"
                            data-price="{{ $definition->default_price ?? '' }}"
                        >
                            {{ $definition->name }}
                        </option>
                    @endforeach
                </select>

                {{-- FASE 20.X: Quick create new treatment --}}
                <button
                    type="button"
                    class="toggle-quick-create"
                    data-target="quick-create-{{ $visitId }}"
                    style="padding: 0.375rem 0.625rem; background: transparent; color: #0ea5e9; border: 1px solid #0ea5e9; border-radius: 4px; font-size: 0.8125rem; cursor: pointer; width: 100%;"
                >
                    + Crear nuevo tratamiento
                </button>

                {{-- Quick create form (hidden by default) --}}
                <div id="quick-create-{{ $visitId }}" style="display: none; margin-top: 0.75rem; padding: 1rem; background: #f8fafc; border-radius: 4px; border: 1px solid #e2e8f0;">
                    <h4 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; font-weight: 600;">Nuevo tratamiento</h4>
                    <div style="margin-bottom: 0.75rem;">
                        <label for="quick-name-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-size: 0.8125rem; font-weight: 500;">
                            Nombre <span style="color: #e11d48;">*</span>
                        </label>
                        <input
                            type="text"
                            id="quick-name-{{ $visitId }}"
                            placeholder="ej: Empaste composite molar"
                            style="width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.8125rem;"
                        >
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label for="quick-price-{{ $visitId }}" style="display: block; margin-bottom: 0.25rem; font-size: 0.8125rem; font-weight: 500;">
                            Precio base (opcional)
                        </label>
                        <input
                            type="number"
                            id="quick-price-{{ $visitId }}"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            style="width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.8125rem;"
                        >
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button
                            type="button"
                            class="save-quick-create"
                            data-visit-id="{{ $visitId }}"
                            data-catalog-select="catalog-{{ $visitId }}"
                            style="flex: 1; padding: 0.375rem 0.75rem; background: #0ea5e9; color: white; border: none; border-radius: 4px; font-size: 0.8125rem; cursor: pointer;"
                        >
                            Guardar en catálogo
                        </button>
                        <button
                            type="button"
                            class="toggle-quick-create"
                            data-target="quick-create-{{ $visitId }}"
                            style="flex: 1; padding: 0.375rem 0.75rem; background: transparent; color: #64748b; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8125rem; cursor: pointer;"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
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
                <small style="display: block; margin-top: 0.25rem; font-size: 0.75rem; color: #64748b;">
                    El importe se carga del catálogo pero es editable para esta visita
                </small>
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
// FASE 20.X: Event delegation global para HTMX compatibility
(function() {
    'use strict';

    // Track manual edits per visit modal (usando Map para multi-modal support)
    const amountManuallyEdited = new Map();
    const baseAmounts = new Map(); // Store base amount per visit
    const isProgrammaticUpdate = new Map(); // Flag to prevent marking programmatic updates as manual edits

    // Utility: Count teeth from string (ej: "16,23,57" → 3)
    function countTeeth(toothString) {
        if (!toothString || toothString.trim() === '') return 0;
        const teeth = toothString.split(',')
            .map(t => t.trim())
            .filter(t => t !== '');
        return teeth.length;
    }

    // Utility: Calculate suggested amount based on base price and teeth count
    function calculateSuggestedAmount(baseAmount, teethCount) {
        const multiplier = Math.max(1, teethCount);
        return baseAmount * multiplier;
    }

    // Handler para catalog selection - HTMX compatible via event delegation
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('treatment-catalog-select')) {
            const select = e.target;
            const selectedOption = select.options[select.selectedIndex];
            const amountTargetId = select.getAttribute('data-amount-target');
            const amountInput = document.getElementById(amountTargetId);

            if (!amountInput) return;

            // Extract visitId from amount input id (ej: "amount-{visitId}")
            const visitId = amountTargetId.replace('amount-', '');

            if (selectedOption.value) {
                const price = selectedOption.getAttribute('data-price');
                if (price && price !== '' && price !== 'null') {
                    const numPrice = parseFloat(price);
                    if (!isNaN(numPrice) && numPrice > 0) {
                        // Store base amount for this visit
                        baseAmounts.set(visitId, numPrice);

                        // Get tooth input to calculate multiplier
                        const toothInput = document.getElementById('tooth-' + visitId);
                        const teethCount = toothInput ? countTeeth(toothInput.value) : 0;

                        // Calculate suggested amount
                        const suggestedAmount = calculateSuggestedAmount(numPrice, teethCount);

                        // Mark as programmatic update to prevent triggering manual edit flag
                        isProgrammaticUpdate.set(visitId, true);
                        amountInput.value = suggestedAmount.toFixed(2);
                        // Reset flag after event has been processed
                        setTimeout(() => isProgrammaticUpdate.set(visitId, false), 0);

                        // Reset manual edit flag when selecting new treatment
                        amountManuallyEdited.set(visitId, false);
                    } else {
                        amountInput.value = '';
                        baseAmounts.delete(visitId);
                    }
                } else {
                    amountInput.value = '';
                    baseAmounts.delete(visitId);
                }
            } else {
                amountInput.value = '';
                baseAmounts.delete(visitId);
                amountManuallyEdited.delete(visitId);
            }
        }
    });

    // Handler para tooth input - recalculate amount when teeth change
    document.addEventListener('input', function(e) {
        if (e.target.id && e.target.id.startsWith('tooth-')) {
            const visitId = e.target.id.replace('tooth-', '');
            const amountInput = document.getElementById('amount-' + visitId);
            const baseAmount = baseAmounts.get(visitId);

            // ALWAYS recalculate when user modifies teeth
            // If user changes teeth, they want Aura to recalculate
            if (amountInput && baseAmount) {
                const teethCount = countTeeth(e.target.value);
                const suggestedAmount = calculateSuggestedAmount(baseAmount, teethCount);

                // Mark as programmatic update to prevent triggering manual edit flag
                isProgrammaticUpdate.set(visitId, true);
                amountInput.value = suggestedAmount.toFixed(2);
                // Reset flag after event has been processed
                setTimeout(() => isProgrammaticUpdate.set(visitId, false), 0);

                // Reset manual edit flag - user is asking for recalculation
                amountManuallyEdited.set(visitId, false);
            }
        }

        // Handler para amount input - mark as manually edited
        if (e.target.id && e.target.id.startsWith('amount-')) {
            const visitId = e.target.id.replace('amount-', '');

            // Only mark as manual edit if this is NOT a programmatic update
            if (!isProgrammaticUpdate.get(visitId)) {
                // Mark that user has manually edited the amount
                // From now on, Aura won't recalculate automatically
                amountManuallyEdited.set(visitId, true);
            }
        }
    });

    // Handler para toggle quick create
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('toggle-quick-create')) {
            const targetId = e.target.getAttribute('data-target');
            const quickCreate = document.getElementById(targetId);
            if (!quickCreate) return;

            const isHidden = quickCreate.style.display === 'none';
            quickCreate.style.display = isHidden ? 'block' : 'none';

            if (isHidden) {
                // Clear form when opening
                const visitId = targetId.replace('quick-create-', '');
                const nameInput = document.getElementById('quick-name-' + visitId);
                const priceInput = document.getElementById('quick-price-' + visitId);
                if (nameInput) nameInput.value = '';
                if (priceInput) priceInput.value = '';
            }
        }
    });

    // Handler para save quick create
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('save-quick-create')) {
            const visitId = e.target.getAttribute('data-visit-id');
            const catalogSelectId = e.target.getAttribute('data-catalog-select');

            const nameInput = document.getElementById('quick-name-' + visitId);
            const priceInput = document.getElementById('quick-price-' + visitId);

            if (!nameInput) return;

            const name = nameInput.value.trim();
            const price = priceInput ? priceInput.value : '';

            if (!name) {
                alert('El nombre del tratamiento es obligatorio');
                return;
            }

            // Create treatment in catalog via AJAX
            fetch('{{ route('workspace.treatments.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    default_price: price || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById(catalogSelectId);
                    if (select) {
                        const option = document.createElement('option');
                        option.value = data.treatment.id;
                        option.setAttribute('data-price', data.treatment.default_price || '');
                        option.text = data.treatment.name;

                        // Insert alphabetically
                        let inserted = false;
                        for (let i = 1; i < select.options.length; i++) {
                            if (select.options[i].text > option.text) {
                                select.insertBefore(option, select.options[i]);
                                inserted = true;
                                break;
                            }
                        }
                        if (!inserted) {
                            select.add(option);
                        }

                        // Select the newly created treatment
                        select.value = data.treatment.id;

                        // Trigger change event to populate amount
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Hide quick create form
                    const quickCreate = document.getElementById('quick-create-' + visitId);
                    if (quickCreate) quickCreate.style.display = 'none';
                } else {
                    alert('Error al crear tratamiento: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                alert('Error al crear tratamiento: ' + error.message);
            });
        }
    });
})();
</script>
