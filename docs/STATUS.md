# Aura — Project Status

## Última sesión
Fecha: 2025-12-23
Estado: FASE 11.2 COMPLETADA

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

## Rate limits activos
- api-read: 120/min
- api-write: 30/min
- api-payments: 10/min

## Tests
- Todos los tests API en verde
- 0 deuda técnica abierta

## Próxima fase prevista
FASE 11.3 — Idempotencia en pagos (evitar duplicados)
Alternativa: FASE 12 — OpenAPI / Swagger como contrato vivo

## Regla para el asistente
NO avanzar de fase sin confirmación explícita.
NO reescribir código ya validado.
