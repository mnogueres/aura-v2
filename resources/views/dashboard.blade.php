@extends('layouts.aura')

@section('header', 'Inicio')

@section('content')
<div class="aura-dashboard-welcome">
    <h2 class="aura-welcome-title">Bienvenido a Aura</h2>
    <p class="aura-welcome-text">Sistema de gestión clínica</p>

    <div class="aura-dashboard-cards">
        <!-- Card: Pacientes -->
        <a href="{{ route('patients.index') }}" class="aura-card">
            <div class="aura-card-header">
                <h3 class="aura-card-title">Pacientes</h3>
                <span class="aura-card-arrow">→</span>
            </div>
            <p class="aura-card-text">
                Consulta y gestiona la información de tus pacientes
            </p>
        </a>

        <!-- Card: API Docs (solo dev) -->
        @if(app()->environment('local'))
        <a href="{{ route('docs.api') }}" class="aura-card aura-card-muted">
            <div class="aura-card-header">
                <h3 class="aura-card-title">API Docs</h3>
                <span class="aura-card-arrow">→</span>
            </div>
            <p class="aura-card-text">
                Documentación técnica de la API (solo desarrollo)
            </p>
        </a>
        @endif
    </div>
</div>
@endsection
