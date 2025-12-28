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
                style="cursor: default;">
                <div class="aura-patient-main">
                    <h3 class="aura-patient-name" x-text="professional.name"></h3>
                    <span class="aura-patient-dni" x-text="getRoleLabel(professional.role)"></span>
                </div>
                <span
                    class="aura-status-badge"
                    :class="professional.status === 'Activo' ? 'active' : 'inactive'"
                    x-text="professional.status"></span>
            </div>
        </template>
    </div>
</div>
