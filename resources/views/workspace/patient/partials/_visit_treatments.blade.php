{{-- FASE 20.3-20.4: Partial para lista de tratamientos (HTMX swap target) --}}
@if($clinicalVisit && $clinicalVisit->treatments->isNotEmpty())
    <h3 class="aura-treatments-title">Tratamientos realizados:</h3>
    <ul class="aura-treatments-list">
        @foreach($clinicalVisit->treatments as $treatment)
            @include('workspace.patient.partials._visit_treatment_item', ['treatment' => $treatment, 'visitId' => $clinicalVisit->id])
        @endforeach
    </ul>
@else
    <p class="aura-no-treatments">Revisión sin tratamientos realizados</p>
@endif

<script>
function toggleEditTreatment(treatmentId) {
    const view = document.getElementById('treatment-view-' + treatmentId);
    const edit = document.getElementById('treatment-edit-' + treatmentId);
    const actions = document.getElementById('treatment-actions-' + treatmentId);

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

function showDeleteConfirmation(treatmentId) {
    const actions = document.getElementById('treatment-actions-' + treatmentId);
    const deleteConfirm = document.getElementById('treatment-delete-confirm-' + treatmentId);

    actions.style.display = 'none';
    deleteConfirm.style.display = 'flex';
}

function confirmDelete(treatmentId) {
    const deleteForm = document.getElementById('delete-form-' + treatmentId);
    deleteForm.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
}

function cancelDelete(treatmentId) {
    const actions = document.getElementById('treatment-actions-' + treatmentId);
    const deleteConfirm = document.getElementById('treatment-delete-confirm-' + treatmentId);

    deleteConfirm.style.display = 'none';
    actions.style.display = 'flex';
}
</script>
