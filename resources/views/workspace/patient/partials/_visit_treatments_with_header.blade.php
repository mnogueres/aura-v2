{{-- Main response: treatments list --}}
@include('workspace.patient.partials._visit_treatments', ['clinicalVisit' => $clinicalVisit])

{{-- OOB swap: update visit header to sync treatments_count (FASE 20.6 bug fix) --}}
@include('workspace.patient.partials._visit_header', ['visit' => $clinicalVisit, 'oob' => true])
