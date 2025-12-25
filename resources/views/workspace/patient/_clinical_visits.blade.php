{{-- Historial de Visitas Clínicas (FASE 17) --}}
<div class="aura-timeline-block">
    <h2 class="aura-block-title">Historial de Visitas</h2>

    @if($clinicalVisits->isEmpty())
        {{-- Ejemplos clínicos (solo cuando no hay visitas reales) --}}
        <div class="aura-visits-list">
            <details class="aura-visit-item aura-visit-example">
                <summary class="aura-visit-summary">
                    <div class="aura-visit-header">
                        <span class="aura-visit-date">15 dic 2024, 10:30</span>
                        <span class="aura-visit-professional">Visita con Dr. García</span>
                        <span class="aura-visit-badge">2 tratamientos</span>
                        <span class="aura-example-badge">Ejemplo</span>
                    </div>
                    <p class="aura-visit-summary-text">Paciente refiere dolor en molar inferior derecho</p>
                </summary>

                <div class="aura-visit-details">
                    <h3 class="aura-treatments-title">Tratamientos realizados:</h3>
                    <ul class="aura-treatments-list">
                        <li class="aura-treatment-item">
                            <span class="aura-treatment-type">Empaste</span>
                            <span class="aura-treatment-tooth">(Pieza: 16)</span>
                            <span class="aura-treatment-amount">65,00 €</span>
                        </li>
                        <li class="aura-treatment-item">
                            <span class="aura-treatment-type">Limpieza general</span>
                            <span class="aura-treatment-amount">45,00 €</span>
                        </li>
                    </ul>
                </div>
            </details>

            <details class="aura-visit-item aura-visit-example">
                <summary class="aura-visit-summary">
                    <div class="aura-visit-header">
                        <span class="aura-visit-date">28 nov 2024, 16:00</span>
                        <span class="aura-visit-professional">Visita con Dra. Pérez</span>
                        <span class="aura-example-badge">Ejemplo</span>
                    </div>
                    <p class="aura-visit-summary-text">Revisión anual preventiva</p>
                </summary>

                <div class="aura-visit-details">
                    <p class="aura-no-treatments">Visita sin tratamientos (revisión o valoración)</p>
                </div>
            </details>
        </div>
    @else
        <div class="aura-visits-list">
            @foreach($clinicalVisits as $visit)
                <details class="aura-visit-item">
                    <summary class="aura-visit-summary">
                        <div class="aura-visit-header">
                            <span class="aura-visit-date">
                                {{ $visit->occurred_at->format('d M Y, H:i') }}
                            </span>
                            <span class="aura-visit-professional">
                                Visita con {{ $visit->professional_name }}
                            </span>
                            @if($visit->treatments_count > 0)
                                <span class="aura-visit-badge">
                                    {{ $visit->treatments_count }} {{ $visit->treatments_count === 1 ? 'tratamiento' : 'tratamientos' }}
                                </span>
                            @endif
                        </div>
                        @if($visit->summary)
                            <p class="aura-visit-summary-text">{{ $visit->summary }}</p>
                        @endif
                    </summary>

                    <div class="aura-visit-details">
                        @if($visit->treatments->isNotEmpty())
                            <h3 class="aura-treatments-title">Tratamientos realizados:</h3>
                            <ul class="aura-treatments-list">
                                @foreach($visit->treatments as $treatment)
                                    <li class="aura-treatment-item">
                                        <span class="aura-treatment-type">{{ $treatment->type }}</span>
                                        @if($treatment->tooth)
                                            <span class="aura-treatment-tooth">(Pieza: {{ $treatment->tooth }})</span>
                                        @endif
                                        @if($treatment->amount)
                                            <span class="aura-treatment-amount">{{ number_format($treatment->amount, 2) }} €</span>
                                        @endif
                                        @if($treatment->notes)
                                            <p class="aura-treatment-notes">{{ $treatment->notes }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="aura-no-treatments">Visita sin tratamientos (revisión o valoración)</p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</div>
