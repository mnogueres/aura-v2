{{-- FASE 21.3: Canonical treatments content for HTMX updates --}}
<div id="treatments-content" x-data="{
        search: '',
        treatments: {{ json_encode($treatments->map(function($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'default_price' => $t->default_price,
                'active' => $t->active,
                'status' => $t->active ? 'Activo' : 'Inactivo',
                'price_label' => $t->default_price ? 'Precio ref: ' . number_format($t->default_price, 2) . '€' : 'Sin precio de referencia',
            ];
        })->values()) }},
        get filteredTreatments() {
            if (this.search === '') return this.treatments;

            const query = this.search.toLowerCase();
            return this.treatments.filter(treatment =>
                treatment.name.toLowerCase().includes(query)
            );
        }
    }">
    <!-- Buscador -->
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre..."
            x-model="search"
            autocomplete="off">
    </div>

    <!-- Listado -->
    <div id="treatments-list" class="aura-patient-list">
        <template x-for="treatment in filteredTreatments" :key="treatment.id">
            <div
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                style="cursor: default;"
                @mouseenter="$el.querySelector('.aura-row__actions').style.opacity = '1'"
                @mouseleave="$el.querySelector('.aura-row__actions').style.opacity = '0'">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="treatment.name"></h3>
                    <span class="aura-patient-dni" x-text="treatment.price_label"></span>
                </div>

                <div class="aura-row__status">
                    <span
                        class="aura-status-badge"
                        :class="treatment.status === 'Activo' ? 'active' : 'inactive'"
                        x-text="treatment.status"></span>
                </div>

                <div class="aura-row__actions" style="opacity: 0; transition: opacity 0.15s ease;">
                    <button
                        @click.stop="openEditTreatmentModal(treatment.id, treatment.name, treatment.default_price)"
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

                    <form
                        :hx-patch="`/workspace/treatment-definitions/${treatment.id}/toggle-active`"
                        hx-target="#treatments-content"
                        hx-swap="outerHTML"
                        hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                        style="display: inline;"
                        @click.stop>
                        <button
                            type="submit"
                            :style="{
                                padding: '0.375rem 0.625rem',
                                background: 'transparent',
                                color: treatment.active ? '#dc2626' : '#059669',
                                border: treatment.active ? '1px solid #fecaca' : '1px solid #bbf7d0',
                                borderRadius: '4px',
                                fontSize: '0.8125rem',
                                cursor: 'pointer',
                                transition: 'all 0.15s'
                            }"
                            @mouseenter="$el.style.background = treatment.active ? '#fef2f2' : '#f0fdf4'"
                            @mouseleave="$el.style.background = 'transparent'"
                            :title="treatment.active ? 'Desactivar' : 'Activar'"
                            x-text="treatment.active ? '⏸' : '▶'">
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</div>
