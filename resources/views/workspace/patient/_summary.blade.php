<details class="aura-summary">
    <summary class="aura-summary-toggle">
        <div class="aura-summary-header">
            <h2 class="aura-summary-title">Resumen del Paciente</h2>
            <div class="aura-summary-id">ID: {{ $summary['patient_id'] }}</div>
        </div>
    </summary>

    <div class="aura-summary-grid">
        <div class="aura-summary-card">
            <div class="aura-summary-label">Facturas</div>
            <div class="aura-summary-value">{{ $summary['invoices_count'] ?? 0 }}</div>
        </div>

        <div class="aura-summary-card">
            <div class="aura-summary-label">Pagos</div>
            <div class="aura-summary-value">{{ $summary['payments_count'] ?? 0 }}</div>
        </div>

        <div class="aura-summary-card">
            <div class="aura-summary-label">Total Facturado</div>
            <div class="aura-summary-value aura-summary-amount">
                {{ number_format($summary['total_invoiced_amount'] ?? 0, 2) }} €
            </div>
        </div>

        <div class="aura-summary-card">
            <div class="aura-summary-label">Total Pagado</div>
            <div class="aura-summary-value aura-summary-amount">
                {{ number_format($summary['total_paid_amount'] ?? 0, 2) }} €
            </div>
        </div>
    </div>

    @if(isset($summary['last_activity_at']))
    <div class="aura-summary-footer">
        <span class="aura-summary-meta">Última actividad: {{ \Carbon\Carbon::parse($summary['last_activity_at'])->format('d/m/Y H:i') }}</span>
    </div>
    @endif
</details>
