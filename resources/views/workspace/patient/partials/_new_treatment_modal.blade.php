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
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 0.5rem;"
                    onchange="handleCatalogSelection_{{ $visitId }}(this)"
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
                    onclick="toggleQuickCreateTreatment_{{ $visitId }}()"
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
                            onclick="saveQuickCreateTreatment_{{ $visitId }}()"
                            style="flex: 1; padding: 0.375rem 0.75rem; background: #0ea5e9; color: white; border: none; border-radius: 4px; font-size: 0.8125rem; cursor: pointer;"
                        >
                            Guardar en catálogo
                        </button>
                        <button
                            type="button"
                            onclick="toggleQuickCreateTreatment_{{ $visitId }}()"
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
// FASE 20.X: Handle catalog selection (REQUIRED - no manual entry)
function handleCatalogSelection_{{ $visitId }}(select) {
    const selectedOption = select.options[select.selectedIndex];
    const amountInput = document.getElementById('amount-{{ $visitId }}');

    if (selectedOption.value) {
        // Catalog item selected: populate amount if available
        const price = selectedOption.getAttribute('data-price');
        // Always set the price (even if empty) to show what will be used
        if (price && price !== '' && price !== 'null') {
            amountInput.value = price;
        } else {
            // Clear but user must enter amount
            amountInput.value = '';
        }
        // User can still edit amount for this specific visit
    } else {
        // No selection: clear amount
        amountInput.value = '';
    }
}

// FASE 20.X: Toggle quick create treatment form
function toggleQuickCreateTreatment_{{ $visitId }}() {
    const quickCreate = document.getElementById('quick-create-{{ $visitId }}');
    const isHidden = quickCreate.style.display === 'none';

    quickCreate.style.display = isHidden ? 'block' : 'none';

    if (isHidden) {
        // Clear form
        document.getElementById('quick-name-{{ $visitId }}').value = '';
        document.getElementById('quick-price-{{ $visitId }}').value = '';
    }
}

// FASE 20.X: Save quick create treatment to catalog
function saveQuickCreateTreatment_{{ $visitId }}() {
    const name = document.getElementById('quick-name-{{ $visitId }}').value.trim();
    const price = document.getElementById('quick-price-{{ $visitId }}').value;

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
            // Add new treatment to select
            const select = document.getElementById('catalog-{{ $visitId }}');
            const option = document.createElement('option');
            option.value = data.treatment.id;
            option.setAttribute('data-name', data.treatment.name);
            option.setAttribute('data-price', data.treatment.default_price || '');
            option.text = data.treatment.name; // Only name, no price in parentheses

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
            select.dispatchEvent(new Event('change'));

            // Hide quick create form
            toggleQuickCreateTreatment_{{ $visitId }}();
        } else {
            alert('Error al crear tratamiento: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        alert('Error al crear tratamiento: ' + error.message);
    });
}
</script>
