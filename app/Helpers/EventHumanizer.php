<?php

namespace App\Helpers;

class EventHumanizer
{
    /**
     * Event name to human-readable Spanish map
     */
    private static array $eventMap = [
        // CRM Events
        'crm.patient.created' => 'Paciente creado',
        'crm.patient.updated' => 'Paciente actualizado',
        'crm.patient.deleted' => 'Paciente eliminado',

        // Billing Events - Invoices
        'billing.invoice.created' => 'Factura creada',
        'billing.invoice.issued' => 'Factura emitida',
        'billing.invoice.voided' => 'Factura anulada',
        'billing.invoice.updated' => 'Factura actualizada',

        // Billing Events - Payments
        'billing.payment.recorded' => 'Pago registrado',
        'billing.payment.applied' => 'Pago aplicado',
        'billing.payment.refunded' => 'Pago reembolsado',

        // Platform Events
        'platform.rate_limited' => 'Límite de tasa alcanzado',
        'platform.idempotency.replayed' => 'Petición repetida',
        'platform.idempotency.conflict' => 'Conflicto de idempotencia',
    ];

    /**
     * Convert event name to human-readable Spanish
     */
    public static function humanize(string $eventName): string
    {
        return self::$eventMap[$eventName] ?? $eventName;
    }

    /**
     * Get icon for event type
     */
    public static function getIcon(string $eventName): string
    {
        // Return appropriate SVG icon based on event category
        if (str_starts_with($eventName, 'crm.patient.')) {
            return '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>';
        }

        if (str_starts_with($eventName, 'billing.invoice.')) {
            return '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>';
        }

        if (str_starts_with($eventName, 'billing.payment.')) {
            return '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>';
        }

        if (str_starts_with($eventName, 'platform.')) {
            return '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>';
        }

        // Default icon
        return '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>';
    }

    /**
     * Get color class for event type
     */
    public static function getColorClass(string $eventName): string
    {
        if (str_contains($eventName, '.created')) {
            return 'aura-event-created';
        }

        if (str_contains($eventName, '.updated')) {
            return 'aura-event-updated';
        }

        if (str_contains($eventName, '.deleted') || str_contains($eventName, '.voided')) {
            return 'aura-event-deleted';
        }

        if (str_contains($eventName, '.payment.') || str_contains($eventName, '.applied')) {
            return 'aura-event-payment';
        }

        return 'aura-event-default';
    }
}
