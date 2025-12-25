# Aura — Project Status

## Última sesión
Fecha: 2025-12-25
Estado: API v1 CONGELADA

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
- FASE 14.1: Patient Timeline Projection (timeline cronológico por paciente)
- FASE 14.2: Patient Summary Projection (contadores y totales agregados)
- FASE 14.3: Billing Timeline Projection (timeline financiero con montos)
- FASE 14.4: Audit Trail Projection (audit técnico platform-wide)
- FASE 15.1: Workspace API endpoints (summary, timeline, billing, audit)
- FASE 15.2: Workspace controllers + repositories
- FASE 15.3: Workspace UI integration con Aura design system
- FASE 12: OpenAPI specification (docs/openapi/openapi.yaml)
- FASE 12.x: Swagger UI integration (dev-only)

## Rate limits activos
- api-read: 120/min
- api-write: 30/min
- api-payments: 10/min

## Tests
- Todos los tests API en verde
- Tests de proyecciones en verde (14/14 Audit Trail, 8/8 Billing Timeline, 9/9 Patient Summary, 12/12 Patient Timeline)
- 0 deuda técnica abierta

## Sistema de eventos y capa de lectura
Sistema de eventos (FASE 13) y capa de lectura (FASE 14) completamente operativos.
- Outbox Pattern con workers funcionales
- 4 read models en producción (Patient Timeline, Patient Summary, Billing Timeline, Audit Trail)
- Idempotencia garantizada en proyecciones
- Separation of concerns: dominio escribe, proyecciones leen

## API v1 — Contrato congelado
**Fecha de congelación:** 2025-12-25

API v1 está congelada como contrato estable:
- No se eliminarán endpoints existentes
- No se cambiarán contratos (request/response)
- No se romperán schemas
- Cambios compatibles permitidos (nuevos endpoints, campos opcionales)
- Cambios incompatibles requieren /v2

**Documentación:**
- Política de versionado: `docs/API_VERSIONING.md`
- OpenAPI spec: `docs/openapi/openapi.yaml`
- Swagger UI: `http://localhost:8000/docs/api` (dev-only)

## Próxima fase prevista
Pendiente de definición por el usuario

## Regla para el asistente
NO avanzar de fase sin confirmación explícita.
NO reescribir código ya validado.
