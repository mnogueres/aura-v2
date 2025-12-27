@if(empty($clinicalVisits))
    <div class="aura-empty-state">
        <p>Este paciente aún no tiene visitas clínicas registradas.</p>
    </div>
@else
    <div class="aura-visits-list">
        @foreach($clinicalVisits as $visit)
            <details class="aura-visit-item">
                @include('workspace.patient.partials._visit_header', ['visit' => $visit])

                <div class="aura-visit-details">
                    {{-- FASE 20.3: Botón PROVISIONAL para añadir tratamiento --}}
                    {{-- IMPORTANTE: Este botón y su estilo son PROVISIONALES --}}
                    {{-- NO establecen el sistema visual definitivo de Aura --}}
                    <div style="margin-bottom: 1rem;">
                        <button
                            onclick="document.getElementById('new-treatment-modal-{{ $visit->id }}').style.display = 'flex'"
                            style="padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 0.8125rem;"
                        >
                            + Añadir tratamiento
                        </button>
                    </div>

                    <div id="treatments-list-{{ $visit->id }}">
                        @php($clinicalVisit = $visit)
                        @include('workspace.patient.partials._visit_treatments', ['clinicalVisit' => $visit])
                    </div>

                    {{-- Include modal for this visit --}}
                    @include('workspace.patient.partials._new_treatment_modal', ['visitId' => $visit->id])
                </div>
            </details>
        @endforeach
    </div>

    @if($visitsMeta && $visitsMeta['last_page'] > 1)
    <div class="aura-pagination">
        <button
            hx-get="{{ route('workspace.patient.show', ['patient' => $patientId, 'visits_page' => max(1, $visitsMeta['current_page'] - 1), 'partial' => 'visits']) }}"
            hx-target="#visits-content"
            hx-swap="innerHTML"
            class="aura-pagination-btn {{ $visitsMeta['current_page'] <= 1 ? 'disabled' : '' }}"
            {{ $visitsMeta['current_page'] <= 1 ? 'disabled' : '' }}>
            Anterior
        </button>

        <div class="aura-pagination-info">
            Página {{ $visitsMeta['current_page'] }} de {{ $visitsMeta['last_page'] }}
        </div>

        <button
            hx-get="{{ route('workspace.patient.show', ['patient' => $patientId, 'visits_page' => min($visitsMeta['last_page'], $visitsMeta['current_page'] + 1), 'partial' => 'visits']) }}"
            hx-target="#visits-content"
            hx-swap="innerHTML"
            class="aura-pagination-btn {{ $visitsMeta['current_page'] >= $visitsMeta['last_page'] ? 'disabled' : '' }}"
            {{ $visitsMeta['current_page'] >= $visitsMeta['last_page'] ? 'disabled' : '' }}>
            Siguiente
        </button>
    </div>
    @endif

    <script>
    function toggleEditVisit(visitId) {
        const view = document.getElementById('visit-view-' + visitId);
        const edit = document.getElementById('visit-edit-' + visitId);
        const actions = document.getElementById('visit-actions-' + visitId);

        if (edit.style.display === 'none') {
            view.style.display = 'none';
            edit.style.display = 'flex';
            actions.style.display = 'none';
        } else {
            view.style.display = 'block';
            edit.style.display = 'none';
            actions.style.display = 'flex';
        }
    }

    function showDeleteVisitConfirmation(visitId, treatmentsCount) {
        const actions = document.getElementById('visit-actions-' + visitId);

        if (treatmentsCount > 0) {
            // Show blocked message
            const blocked = document.getElementById('visit-delete-blocked-' + visitId);
            actions.style.display = 'none';
            blocked.style.display = 'flex';
        } else {
            // Show confirmation
            const deleteConfirm = document.getElementById('visit-delete-confirm-' + visitId);
            actions.style.display = 'none';
            deleteConfirm.style.display = 'flex';
        }
    }

    function confirmDeleteVisit(visitId) {
        const deleteForm = document.getElementById('delete-visit-form-' + visitId);
        deleteForm.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
    }

    function cancelDeleteVisit(visitId) {
        const actions = document.getElementById('visit-actions-' + visitId);
        const deleteConfirm = document.getElementById('visit-delete-confirm-' + visitId);
        const blocked = document.getElementById('visit-delete-blocked-' + visitId);

        deleteConfirm.style.display = 'none';
        blocked.style.display = 'none';
        actions.style.display = 'flex';
    }
    </script>
@endif
