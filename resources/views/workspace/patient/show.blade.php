@extends('layouts.aura')

@section('header', 'Workspace del Paciente')

@section('content')
<div class="aura-workspace">
    @if($summary)
        @include('workspace.patient._summary', ['summary' => $summary])
    @else
        <div class="aura-empty-state">
            <p>No se encontró información del paciente</p>
        </div>
    @endif

    <div class="aura-workspace-timelines">
        @include('workspace.patient._timeline', [
            'timeline' => $timeline,
            'timelineMeta' => $timelineMeta,
            'patientId' => $patientId
        ])

        @include('workspace.patient._billing', [
            'billing' => $billing,
            'billingMeta' => $billingMeta,
            'patientId' => $patientId
        ])
    </div>
</div>
@endsection
