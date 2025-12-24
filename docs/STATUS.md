# Aura — Project Status

## Última sesión
Fecha: 2025-12-24
Estado: FASE 13 COMPLETADA

## Arquitectura validada
- Multi-tenant por clinic_id (ClinicScope global)
- Policies activas y testeadas
- No se persisten agregados financieros
- API REST versionada /api/v1
- Envelope estándar:
  - data
  - meta.pagination
  - meta.request_id
  - error (code, message, details)

## Fases completadas
- FASE 1–10: Core dominio + API Resources + tests
- FASE 11.1: Error handling centralizado (422, 403, 404, 429)
- FASE 11.2: Rate limiting inteligente (user + clinic + IP)
- FASE 11.3: Idempotencia (Idempotency-Key en POST, TTL 24h)
- FASE 13.1: Taxonomía de eventos de dominio (10 eventos)
- FASE 13.2: Event classes (CRM, Billing, Platform)
- FASE 13.3: EventService + emisión post-commit
- FASE 13.4: Outbox Pattern (tabla outbox_events, logging transaccional)
- FASE 13.5: Workers/Consumers (OutboxEventConsumer, ProcessOutboxEvents job/command)

## Rate limits activos
- api-read: 120/min
- api-write: 30/min
- api-payments: 10/min

## Tests
- Todos los tests API en verde
- 0 deuda técnica abierta

## Próxima fase prevista
FASE 12 — OpenAPI / Swagger como contrato vivo
Alternativa: FASE 14 — Frontend (vistas según aura-rules.md)

## Regla para el asistente
NO avanzar de fase sin confirmación explícita.
NO reescribir código ya validado.
