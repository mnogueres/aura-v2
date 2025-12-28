{{-- FASE 20.7 BLOQUE 2: Treatments list with Aura canonical hover --}}

@if($treatments->isEmpty())
    <div style="
        padding: 3rem 2rem;
        text-align: center;
        color: #94a3b8;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
    ">
        <p style="margin: 0; font-size: 0.875rem;">
            Aún no has creado tratamientos en tu catálogo
        </p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem;">
            Crea tu primer tratamiento para empezar a usarlo en las visitas
        </p>
    </div>
@else
    <div class="aura-treatments-catalog">
        @foreach($treatments as $treatment)
            <div
                class="aura-treatment-row"
                data-treatment-id="{{ $treatment->id }}"
                style="
                    position: relative;
                    display: grid;
                    grid-template-columns: 1fr auto auto;
                    gap: 1rem;
                    align-items: center;
                    padding: 0.875rem 1rem;
                    border-bottom: 1px solid #f1f5f9;
                    transition: background 0.15s ease;
                "
                onmouseover="
                    this.style.background = '#f8fafc';
                    this.querySelector('.aura-treatment-actions').style.opacity = '1';
                "
                onmouseout="
                    this.style.background = 'transparent';
                    this.querySelector('.aura-treatment-actions').style.opacity = '0';
                "
            >
                {{-- Treatment Info --}}
                <div style="min-width: 0;">
                    <div id="treatment-view-{{ $treatment->id }}">
                        <h4 style="
                            margin: 0 0 0.25rem 0;
                            font-size: 0.9375rem;
                            font-weight: 500;
                            color: #1e293b;
                        ">
                            {{ $treatment->name }}
                        </h4>
                        @if($treatment->default_price)
                            <span style="
                                font-size: 0.8125rem;
                                color: #64748b;
                            ">
                                Precio ref: {{ number_format($treatment->default_price, 2) }}€
                            </span>
                        @else
                            <span style="
                                font-size: 0.8125rem;
                                color: #94a3b8;
                                font-style: italic;
                            ">
                                Sin precio de referencia
                            </span>
                        @endif
                    </div>

                    {{-- Inline Edit Form (hidden by default) --}}
                    <div id="treatment-edit-{{ $treatment->id }}" style="display: none;">
                        <form
                            hx-patch="{{ route('workspace.treatment-definitions.update', ['treatmentDefinition' => $treatment->id]) }}"
                            hx-target="#treatments-list"
                            hx-swap="innerHTML"
                            style="display: flex; gap: 0.5rem; align-items: center;"
                        >
                            @csrf
                            <input
                                type="text"
                                name="name"
                                value="{{ $treatment->name }}"
                                required
                                style="
                                    padding: 0.375rem 0.5rem;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 4px;
                                    font-size: 0.875rem;
                                    min-width: 200px;
                                "
                            >
                            <input
                                type="number"
                                name="default_price"
                                value="{{ $treatment->default_price }}"
                                step="0.01"
                                min="0"
                                placeholder="Precio"
                                style="
                                    padding: 0.375rem 0.5rem;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 4px;
                                    font-size: 0.875rem;
                                    width: 100px;
                                "
                            >
                            <button
                                type="submit"
                                style="
                                    padding: 0.375rem 0.75rem;
                                    background: #0ea5e9;
                                    color: white;
                                    border: none;
                                    border-radius: 4px;
                                    font-size: 0.8125rem;
                                    cursor: pointer;
                                "
                            >
                                Guardar
                            </button>
                            <button
                                type="button"
                                onclick="toggleEditTreatment('{{ $treatment->id }}')"
                                style="
                                    padding: 0.375rem 0.75rem;
                                    background: transparent;
                                    color: #64748b;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 4px;
                                    font-size: 0.8125rem;
                                    cursor: pointer;
                                "
                            >
                                Cancelar
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div>
                    <span class="aura-status-badge {{ $treatment->active ? 'active' : 'inactive' }}">
                        {{ $treatment->active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                {{-- Actions (visible on hover) --}}
                <div
                    class="aura-treatment-actions"
                    style="
                        display: flex;
                        gap: 0.5rem;
                        opacity: 0;
                        transition: opacity 0.15s ease;
                    "
                >
                    {{-- Edit Button --}}
                    <button
                        onclick="toggleEditTreatment('{{ $treatment->id }}')"
                        style="
                            padding: 0.375rem 0.625rem;
                            background: transparent;
                            color: #64748b;
                            border: 1px solid #e2e8f0;
                            border-radius: 4px;
                            font-size: 0.8125rem;
                            cursor: pointer;
                            transition: all 0.15s;
                        "
                        onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';"
                        onmouseout="this.style.background='transparent'; this.style.borderColor='#e2e8f0';"
                        title="Editar"
                    >
                        ✎
                    </button>

                    {{-- Toggle Active Button --}}
                    <form
                        hx-patch="{{ route('workspace.treatment-definitions.toggle-active', ['treatmentDefinition' => $treatment->id]) }}"
                        hx-target="#treatments-list"
                        hx-swap="innerHTML"
                        style="display: inline;"
                    >
                        @csrf
                        <button
                            type="submit"
                            style="
                                padding: 0.375rem 0.625rem;
                                background: transparent;
                                color: {{ $treatment->active ? '#dc2626' : '#059669' }};
                                border: 1px solid {{ $treatment->active ? '#fecaca' : '#bbf7d0' }};
                                border-radius: 4px;
                                font-size: 0.8125rem;
                                cursor: pointer;
                                transition: all 0.15s;
                            "
                            onmouseover="this.style.background='{{ $treatment->active ? '#fef2f2' : '#f0fdf4' }}';"
                            onmouseout="this.style.background='transparent';"
                            title="{{ $treatment->active ? 'Desactivar' : 'Activar' }}"
                        >
                            {{ $treatment->active ? '⏸' : '▶' }}
                        </button>
                    </form>

                    {{-- Delete Button (FASE 20.7 - conditional on usage) --}}
                    @if($treatment->usage_count === 0)
                        {{-- Show delete if NEVER used --}}
                        <div id="delete-container-{{ $treatment->id }}" style="display: inline;">
                            <button
                                onclick="showDeleteConfirmation('{{ $treatment->id }}')"
                                style="
                                    padding: 0.375rem 0.625rem;
                                    background: transparent;
                                    color: #dc2626;
                                    border: 1px solid #fecaca;
                                    border-radius: 4px;
                                    font-size: 0.8125rem;
                                    cursor: pointer;
                                    transition: all 0.15s;
                                "
                                onmouseover="this.style.background='#fef2f2';"
                                onmouseout="this.style.background='transparent';"
                                title="Eliminar (no usado en visitas)"
                            >
                                🗑
                            </button>
                        </div>

                        {{-- Inline confirmation (Aura style, hidden by default) --}}
                        <div id="delete-confirmation-{{ $treatment->id }}" style="display: none;">
                            <span style="font-size: 0.8125rem; color: #64748b; margin-right: 0.5rem;">¿Eliminar?</span>
                            <form
                                hx-delete="{{ route('workspace.treatment-definitions.destroy', ['treatmentDefinition' => $treatment->id]) }}"
                                hx-target="#treatments-list"
                                hx-swap="innerHTML"
                                style="display: inline;"
                            >
                                @csrf
                                <button
                                    type="submit"
                                    style="
                                        padding: 0.25rem 0.5rem;
                                        background: #dc2626;
                                        color: white;
                                        border: none;
                                        border-radius: 4px;
                                        font-size: 0.75rem;
                                        cursor: pointer;
                                        margin-right: 0.25rem;
                                    "
                                >
                                    Sí
                                </button>
                            </form>
                            <button
                                onclick="hideDeleteConfirmation('{{ $treatment->id }}')"
                                style="
                                    padding: 0.25rem 0.5rem;
                                    background: transparent;
                                    color: #64748b;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 4px;
                                    font-size: 0.75rem;
                                    cursor: pointer;
                                "
                            >
                                No
                            </button>
                        </div>
                    @else
                        {{-- If used in visits, show indicator (optional) --}}
                        <span
                            style="
                                padding: 0.375rem 0.625rem;
                                font-size: 0.75rem;
                                color: #94a3b8;
                            "
                            title="Usado en {{ $treatment->usage_count }} visita(s) - solo se puede desactivar"
                        >

                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <script>
    function toggleEditTreatment(treatmentId) {
        const view = document.getElementById('treatment-view-' + treatmentId);
        const edit = document.getElementById('treatment-edit-' + treatmentId);

        if (edit.style.display === 'none') {
            view.style.display = 'none';
            edit.style.display = 'block';
        } else {
            view.style.display = 'block';
            edit.style.display = 'none';
        }
    }

    function showDeleteConfirmation(treatmentId) {
        const deleteContainer = document.getElementById('delete-container-' + treatmentId);
        const deleteConfirmation = document.getElementById('delete-confirmation-' + treatmentId);

        deleteContainer.style.display = 'none';
        deleteConfirmation.style.display = 'inline-flex';
        deleteConfirmation.style.alignItems = 'center';
    }

    function hideDeleteConfirmation(treatmentId) {
        const deleteContainer = document.getElementById('delete-container-' + treatmentId);
        const deleteConfirmation = document.getElementById('delete-confirmation-' + treatmentId);

        deleteConfirmation.style.display = 'none';
        deleteContainer.style.display = 'inline';
    }
    </script>
@endif
