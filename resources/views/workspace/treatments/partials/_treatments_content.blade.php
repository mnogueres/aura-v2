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
                style="cursor: default;">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="treatment.name"></h3>
                    <span class="aura-patient-dni" x-text="treatment.price_label"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="treatment.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="treatment.status"></span>
            </div>
        </template>
    </div>
</div>
