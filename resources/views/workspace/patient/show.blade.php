@extends('layouts.aura')

@section('header', 'Workspace del Paciente')

@section('content')
<div class="aura-workspace">
    {{-- Patient Identity (Read-only) --}}
    @include('workspace.patient._identity', ['patient' => $patient])

    @if($summary)
        @include('workspace.patient._summary', ['summary' => $summary])
    @else
        <div class="aura-empty-state">
            <p>No se encontró información del paciente</p>
        </div>
    @endif

    {{-- Historial de Visitas Clínicas (FASE 17) --}}
    @include('workspace.patient._clinical_visits', [
        'clinicalVisits' => $clinicalVisits
    ])

    {{-- FASE 17: Timeline técnico oculto (código preservado para referencia)
    @include('workspace.patient._timeline', [
        'timeline' => $timeline,
        'timelineMeta' => $timelineMeta,
        'patientId' => $patientId
    ])
    --}}

    {{-- FASE 17: Billing Timeline ahora va full-width, sin wrapper grid --}}
    @include('workspace.patient._billing', [
        'billing' => $billing,
        'billingMeta' => $billingMeta,
        'patientId' => $patientId
    ])
</div>
@endsection
