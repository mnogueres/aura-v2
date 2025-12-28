{{-- FASE 21.3: Canonical professionals content for HTMX updates --}}
<div id="professionals-content" x-data="{
        search: '',
        professionals: {{ json_encode($professionals->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'role' => $p->role,
                'active' => $p->active,
                'status' => $p->active ? 'Activo' : 'Inactivo',
            ];
        })->values()) }},
        get filteredProfessionals() {
            if (this.search === '') return this.professionals;

            const query = this.search.toLowerCase();
            return this.professionals.filter(professional =>
                professional.name.toLowerCase().includes(query) ||
                this.getRoleLabel(professional.role).toLowerCase().includes(query)
            );
        },
        getRoleLabel(role) {
            const labels = {
                'dentist': 'Odontólogo/a',
                'hygienist': 'Higienista',
                'assistant': 'Asistente',
                'other': 'Otro'
            };
            return labels[role] || role;
        }
    }">
    <!-- Buscador -->
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre o rol..."
            x-model="search"
            autocomplete="off">
    </div>

    <!-- Listado -->
    <div id="professionals-list" class="aura-patient-list">
        <template x-for="professional in filteredProfessionals" :key="professional.id">
            <div
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                style="cursor: default; position: relative;"
                @mouseenter="$el.querySelector('.aura-item-actions').style.opacity = '1'"
                @mouseleave="$el.querySelector('.aura-item-actions').style.opacity = '0'">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="professional.name"></h3>
                    <span class="aura-patient-dni" x-text="getRoleLabel(professional.role)"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="professional.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="professional.status"></span>

                <!-- Action Buttons (visible on hover) -->
                <div class="aura-item-actions" style="
                    display: flex;
                    gap: 0.5rem;
                    opacity: 0;
                    transition: opacity 0.15s ease;
                    margin-left: 1rem;
                ">
                    <!-- Edit Button -->
                    <button
                        @click.stop="openEditProfessionalModal(professional.id, professional.name, professional.role)"
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
                        @mouseenter="$el.style.background='#f1f5f9'; $el.style.borderColor='#cbd5e1'"
                        @mouseleave="$el.style.background='transparent'; $el.style.borderColor='#e2e8f0'"
                        title="Editar">
                        ✎
                    </button>

                    <!-- Toggle Active Button -->
                    <form
                        :hx-patch="professional.active
                            ? `/workspace/professionals/${professional.id}/deactivate`
                            : `/workspace/professionals/${professional.id}/activate`"
                        hx-target="#professionals-content"
                        hx-swap="outerHTML"
                        style="display: inline;"
                        @click.stop>
                        <button
                            type="submit"
                            :style="{
                                padding: '0.375rem 0.625rem',
                                background: 'transparent',
                                color: professional.active ? '#dc2626' : '#059669',
                                border: professional.active ? '1px solid #fecaca' : '1px solid #bbf7d0',
                                borderRadius: '4px',
                                fontSize: '0.8125rem',
                                cursor: 'pointer',
                                transition: 'all 0.15s'
                            }"
                            @mouseenter="$el.style.background = professional.active ? '#fef2f2' : '#f0fdf4'"
                            @mouseleave="$el.style.background = 'transparent'"
                            :title="professional.active ? 'Desactivar' : 'Activar'"
                            x-text="professional.active ? '⏸' : '▶'">
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</div>
