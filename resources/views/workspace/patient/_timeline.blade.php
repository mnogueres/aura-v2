<div class="aura-timeline-section">
    <h3 class="aura-section-title">Timeline del Paciente</h3>

    @if(count($timeline) > 0)
        <div class="aura-timeline">
            @foreach($timeline as $event)
            <div class="aura-timeline-item">
                <div class="aura-timeline-marker"></div>
                <div class="aura-timeline-content">
                    <div class="aura-timeline-header">
                        <span class="aura-timeline-event">{{ $event['event_name'] }}</span>
                        <span class="aura-timeline-date">
                            {{ \Carbon\Carbon::parse($event['occurred_at'])->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($timelineMeta && $timelineMeta['last_page'] > 1)
        <div class="aura-pagination">
            <a
                href="{{ route('workspace.patient.show', ['patient' => $patientId, 'timeline_page' => max(1, $timelineMeta['current_page'] - 1)]) }}"
                class="aura-pagination-btn {{ $timelineMeta['current_page'] <= 1 ? 'disabled' : '' }}">
                Anterior
            </a>

            <div class="aura-pagination-info">
                Página {{ $timelineMeta['current_page'] }} de {{ $timelineMeta['last_page'] }}
            </div>

            <a
                href="{{ route('workspace.patient.show', ['patient' => $patientId, 'timeline_page' => min($timelineMeta['last_page'], $timelineMeta['current_page'] + 1)]) }}"
                class="aura-pagination-btn {{ $timelineMeta['current_page'] >= $timelineMeta['last_page'] ? 'disabled' : '' }}">
                Siguiente
            </a>
        </div>
        @endif
    @else
        <div class="aura-empty-state">
            <p>No hay eventos registrados</p>
        </div>
    @endif
</div>
