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
            <div
                class="aura-patient-item"
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                style="cursor: pointer;"
                @click="window.location.href = `/workspace/patients/${patient.id}`"
                @mouseenter="$el.querySelector('.aura-row__actions').style.opacity = '1'"
                @mouseleave="$el.querySelector('.aura-row__actions').style.opacity = '0'">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="patient.name"></h3>
                    <span class="aura-patient-dni" x-text="patient.dni"></span>
                </div>

                <div class="aura-row__status">
                    <span
                        class="aura-status-badge"
                        :class="patient.status === 'Activo' ? 'active' : 'inactive'"
                        x-text="patient.status"></span>
                </div>

                <div class="aura-row__actions" style="opacity: 0; transition: opacity 0.15s ease;">
                    <button
                        @click.stop="openEditPatientModal(patient.id, patient.name, patient.dni)"
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
                </div>
            </div>
        </template>
    </div>
</div>
