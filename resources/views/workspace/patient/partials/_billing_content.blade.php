@if(count($billing) > 0)
    <div class="aura-billing-timeline">
        @foreach($billing as $event)
        <div class="aura-billing-item {{ \App\Helpers\EventHumanizer::getColorClass($event['event_name']) }}">
            <div class="aura-billing-marker"></div>
            <div class="aura-billing-content">
                <div class="aura-billing-header">
                    <span class="aura-billing-date">
                        {{ \Carbon\Carbon::parse($event['occurred_at'])->format('d M Y, H:i') }}
                    </span>
                    <div class="aura-billing-event-wrapper">
                        <span class="aura-billing-icon">{!! \App\Helpers\EventHumanizer::getIcon($event['event_name']) !!}</span>
                        <span class="aura-billing-event">{{ \App\Helpers\EventHumanizer::humanize($event['event_name']) }}</span>
                    </div>
                    @if(isset($event['amount']))
                        <span class="aura-billing-amount">{{ number_format($event['amount'], 2) }} {{ $event['currency'] ?? 'EUR' }}</span>
                    @endif
                    @if(isset($event['reference_id']))
                        <span class="aura-billing-ref">Ref: {{ $event['reference_id'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($billingMeta && $billingMeta['last_page'] > 1)
    <div class="aura-pagination">
        <button
            hx-get="{{ route('workspace.patient.show', ['patient' => $patientId, 'billing_page' => max(1, $billingMeta['current_page'] - 1), 'partial' => 'billing']) }}"
            hx-target="#billing-content"
            hx-swap="innerHTML"
            class="aura-pagination-btn {{ $billingMeta['current_page'] <= 1 ? 'disabled' : '' }}"
            {{ $billingMeta['current_page'] <= 1 ? 'disabled' : '' }}>
            Anterior
        </button>

        <div class="aura-pagination-info">
            Página {{ $billingMeta['current_page'] }} de {{ $billingMeta['last_page'] }}
        </div>

        <button
            hx-get="{{ route('workspace.patient.show', ['patient' => $patientId, 'billing_page' => min($billingMeta['last_page'], $billingMeta['current_page'] + 1), 'partial' => 'billing']) }}"
            hx-target="#billing-content"
            hx-swap="innerHTML"
            class="aura-pagination-btn {{ $billingMeta['current_page'] >= $billingMeta['last_page'] ? 'disabled' : '' }}"
            {{ $billingMeta['current_page'] >= $billingMeta['last_page'] ? 'disabled' : '' }}>
            Siguiente
        </button>
    </div>
    @endif
@else
    <div class="aura-empty-state">
        <p>No hay facturación asociada a este paciente.</p>
    </div>
@endif
