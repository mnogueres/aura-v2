{{-- FASE 21.3: Canonical patients content for HTMX updates --}}
<div id="patients-content" x-data="{
        search: '',
        patients: {{ json_encode($patients) }},
        get filteredPatients() {
            if (this.search === '') return this.patients;

            const query = this.search.toLowerCase();
            return this.patients.filter(patient =>
                patient.name.toLowerCase().includes(query) ||
                patient.dni.toLowerCase().includes(query)
            );
        }
    }">
    <!-- Buscador -->
    <div class="aura-search" style="margin-bottom: 1.5rem;">
        <input
            type="text"
            class="aura-search-input"
            placeholder="Buscar por nombre o DNI..."
            x-model="search"
            autocomplete="off">
    </div>

    <!-- Listado -->
    <div class="aura-patient-list">
        <template x-for="patient in filteredPatients" :key="patient.id">
            <a
                :href="`/workspace/patients/${patient.id}`"
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="patient.name"></h3>
                    <span class="aura-patient-dni" x-text="patient.dni"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="patient.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="patient.status"></span>
            </a>
        </template>
    </div>
</div>
