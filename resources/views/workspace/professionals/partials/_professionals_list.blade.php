{{-- FASE 21.0/21.1: Professionals list with Alpine.js filtering and Aura canonical hover --}}

@if($professionals->isEmpty())
    <div style="
        padding: 3rem 2rem;
        text-align: center;
        color: #94a3b8;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
    ">
        <p style="margin: 0; font-size: 0.875rem;">
            Aún no hay profesionales en tu catálogo
        </p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem;">
            Crea tu primer profesional para empezar a asignarlos a visitas
        </p>
    </div>
@else
    <div class="aura-professionals-catalog">
        @foreach($professionals as $professional)
            <div
                class="aura-professional-row"
                data-professional-id="{{ $professional->id }}"
                data-professional-name="{{ $professional->name }}"
                data-professional-role="{{ $professional->role }}"
                x-show="(() => {
                    if (search === '') return true;
                    const query = search.toLowerCase();
                    const name = '{{ $professional->name }}'.toLowerCase();
                    const roleLabels = {
                        'dentist': 'odontólogo/a',
                        'hygienist': 'higienista',
                        'assistant': 'asistente',
                        'other': 'otro'
                    };
                    const role = roleLabels['{{ $professional->role }}'] || '{{ $professional->role }}'.toLowerCase();
                    return name.includes(query) || role.includes(query);
                })()"
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
                    this.querySelector('.aura-professional-actions').style.opacity = '1';
                "
                onmouseout="
                    this.style.background = 'transparent';
                    this.querySelector('.aura-professional-actions').style.opacity = '0';
                "
            >
                {{-- Professional Info --}}
                <div style="min-width: 0;">
                    <div id="professional-view-{{ $professional->id }}">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <h4 style="
                                margin: 0;
                                font-size: 0.9375rem;
                                font-weight: 500;
                                color: #1e293b;
                            ">
                                {{ $professional->name }}
                            </h4>
                            @if($professional->user_id)
                                <span style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 0.125rem 0.375rem;
                                    font-size: 0.6875rem;
                                    font-weight: 500;
                                    border-radius: 3px;
                                    background: #e0e7ff;
                                    color: #4338ca;
                                " title="Vinculado a usuario del sistema">
                                    Usuario
                                </span>
                            @endif
                        </div>
                        <span style="
                            font-size: 0.8125rem;
                            color: #64748b;
                        ">
                            @switch($professional->role)
                                @case('dentist') Odontólogo/a @break
                                @case('hygienist') Higienista @break
                                @case('assistant') Asistente @break
                                @case('other') Otro @break
                            @endswitch
                        </span>
                    </div>

                    {{-- Inline Edit Form (hidden by default) --}}
                    <div id="professional-edit-{{ $professional->id }}" style="display: none;">
                        <form
                            hx-patch="{{ route('workspace.professionals.update', ['professional' => $professional->id]) }}"
                            hx-target="#professionals-list"
                            hx-swap="innerHTML"
                            style="display: flex; gap: 0.5rem; align-items: center;"
                        >
                            @csrf
                            <input
                                type="text"
                                name="name"
                                value="{{ $professional->name }}"
                                required
                                style="
                                    padding: 0.375rem 0.5rem;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 4px;
                                    font-size: 0.875rem;
                                    min-width: 200px;
                                "
                            >
                            <select
                                name="role"
                                required
                                style="
                                    padding: 0.375rem 0.5rem;
                                    border: 1px solid #cbd5e1;
                                    border-radius: 4px;
                                    font-size: 0.875rem;
                                "
                            >
                                <option value="dentist" {{ $professional->role === 'dentist' ? 'selected' : '' }}>Odontólogo/a</option>
                                <option value="hygienist" {{ $professional->role === 'hygienist' ? 'selected' : '' }}>Higienista</option>
                                <option value="assistant" {{ $professional->role === 'assistant' ? 'selected' : '' }}>Asistente</option>
                                <option value="other" {{ $professional->role === 'other' ? 'selected' : '' }}>Otro</option>
                            </select>
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
                                onclick="toggleEditProfessional('{{ $professional->id }}')"
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
                    <span class="aura-status-badge {{ $professional->active ? 'active' : 'inactive' }}">
                        {{ $professional->active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                {{-- Actions (visible on hover) --}}
                <div
                    class="aura-professional-actions"
                    style="
                        display: flex;
                        gap: 0.5rem;
                        opacity: 0;
                        transition: opacity 0.15s ease;
                    "
                >
                    {{-- Edit Button --}}
                    <button
                        onclick="toggleEditProfessional('{{ $professional->id }}')"
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

                    {{-- Toggle Active/Inactive Button --}}
                    <form
                        hx-patch="{{ route($professional->active ? 'workspace.professionals.deactivate' : 'workspace.professionals.activate', ['professional' => $professional->id]) }}"
                        hx-target="#professionals-list"
                        hx-swap="innerHTML"
                        style="display: inline;"
                    >
                        @csrf
                        <button
                            type="submit"
                            style="
                                padding: 0.375rem 0.625rem;
                                background: transparent;
                                color: {{ $professional->active ? '#dc2626' : '#059669' }};
                                border: 1px solid {{ $professional->active ? '#fecaca' : '#bbf7d0' }};
                                border-radius: 4px;
                                font-size: 0.8125rem;
                                cursor: pointer;
                                transition: all 0.15s;
                            "
                            onmouseover="this.style.background='{{ $professional->active ? '#fef2f2' : '#f0fdf4' }}';"
                            onmouseout="this.style.background='transparent';"
                            title="{{ $professional->active ? 'Desactivar' : 'Activar' }}"
                        >
                            {{ $professional->active ? '⏸' : '▶' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{-- No results message (FASE 21.1 - BLOQUE 3) --}}
    <div x-show="search !== '' && filteredProfessionals.length === 0" style="
        padding: 3rem 2rem;
        text-align: center;
        color: #94a3b8;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
    ">
        <p style="margin: 0; font-size: 0.875rem;">
            No se encontraron profesionales
        </p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem;">
            Intenta con otros términos de búsqueda
        </p>
    </div>

    <script>
    function toggleEditProfessional(professionalId) {
        const view = document.getElementById('professional-view-' + professionalId);
        const edit = document.getElementById('professional-edit-' + professionalId);

        if (edit.style.display === 'none') {
            view.style.display = 'none';
            edit.style.display = 'block';
        } else {
            view.style.display = 'block';
            edit.style.display = 'none';
        }
    }
    </script>
@endif
