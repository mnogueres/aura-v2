@if(empty($clinicalVisits))
    <div class="aura-empty-state">
        <p>Este paciente aún no tiene visitas clínicas registradas.</p>
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
                        @if($visit->summary)
                            <span class="aura-visit-summary-text">{{ $visit->summary }}</span>
                        @endif
                    </div>
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
                        <p class="aura-no-treatments">Revisión sin tratamientos realizados</p>
                    @endif
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
@endif
