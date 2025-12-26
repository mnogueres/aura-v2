# Aura — Project Status

## Última sesión
Fecha: 2025-12-26
Estado: FASE 19.1 completada - Paginación sin recarga con HTMX

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
- FASE 12: OpenAPI specification (docs/openapi/openapi.yaml)
- FASE 12.x: Swagger UI integration (dev-only)
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
- FASE 16: Clinical Model design (modelo clínico read-only, visitas + tratamientos)
- FASE 17: Clinical Model implementation (tablas, modelos, repositories, UI)
- FASE 18: Validation con datos reales (ValidationSeeder, walkthroughs)
- FASE 19: Producto Vivo (eliminación ejemplos, estados vacíos, datos reales exclusivamente)
- FASE 19.1: Paginación sin recarga (HTMX, sin scroll jump, interacción fluida)

## Rate limits activos
- api-read: 120/min
- api-write: 30/min
- api-payments: 10/min

## Tests
- Todos los tests API en verde
- Tests de proyecciones en verde (14/14 Audit Trail, 8/8 Billing Timeline, 9/9 Patient Summary, 12/12 Patient Timeline)
- 0 deuda técnica abierta

## Sistema de eventos y capa de lectura
Sistema de eventos (FASE 13) y capa de lectura (FASE 14-16) completamente operativos.
- Outbox Pattern con workers funcionales
- 5 read models en producción:
  - Patient Timeline (eventos CRM cronológicos)
  - Patient Summary (contadores y agregados)
  - Billing Timeline (eventos financieros)
  - Audit Trail (eventos técnicos platform-wide)
  - Clinical Visits (visitas clínicas + tratamientos, FASE 16)
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

## FASE 19 — Producto Vivo (Live Product)
**Fecha:** 2025-12-26
**Estado:** ✅ COMPLETADA

### Principios fundamentales
- Documentación `PRODUCT_LIVE.md` creada
- **Regla de oro:** "Si no hay datos reales, no se inventan"
- Estados vacíos son ciudadanos de primera clase, no errores
- Workspace refleja exactamente lo que hay en la base de datos
- Sin ejemplos visuales ficticios, sin simulaciones, sin modo demo

### Cambios implementados

**Filosofía:**
- Eliminados todos los ejemplos visuales del Workspace
- Estados vacíos humanizados ("Este paciente aún no tiene visitas...")
- PatientController: consulta BD real, no datos hardcoded
- Sidebar Aura: sin simulación de auth, solo contexto de clínica

**Navegación:**
- Flujo establecido: Dashboard → Pacientes → Workspace
- Workspace como destino específico, no landing page
- Read-only coherente (sin botones de acción/escritura)

### FASE 19.1 — Paginación sin recarga (HTMX)
**Fecha:** 2025-12-26
**Estado:** ✅ COMPLETADA

### Problema resuelto
- Paginación con recarga completa causaba scroll jump
- Usuario perdía contexto al paginar timelines
- Experiencia disruptiva al navegar entre páginas

### Implementación

**Tecnología añadida:**
- HTMX (14kb) vía CDN en layout Aura
- Declarativo: HTML attributes, no JS custom

**Vistas parciales creadas:**
- `partials/_billing_content.blade.php`: contenido actualizable de billing timeline
- `partials/_visits_content.blade.php`: contenido actualizable de clinical visits

**Controller mejorado:**
- `PatientWorkspaceController`: detecta `?partial=billing/visits`
- Devuelve solo HTML del bloque (sin layout completo)
- Reutiliza mismos endpoints, misma lógica

**Botones de paginación:**
- Atributos HTMX: `hx-get`, `hx-target="#billing-content"`, `hx-swap="innerHTML"`
- Loading state automático (`.htmx-request` con opacity 0.6)
- Disabled states durante carga

### Características logradas
- ✅ **Sin recarga de página completa**
- ✅ **Scroll no se mueve** (usuario mantiene contexto)
- ✅ **Solo actualiza el bloque correspondiente**
- ✅ **Blade sigue siendo fuente de markup** (no SPA)
- ✅ **No frameworks pesados** (HTMX es 14kb)
- ✅ **Interacción fluida** sin pérdida de foco

### Resultado
**Experiencia:** Paginación tan fluida como una SPA, pero sin ser una SPA.

**Arquitectura preservada:**
- Server-side rendering intacto
- Blade templates como fuente de verdad
- Controller decide qué renderizar
- No complejidad en frontend

### Workspace actual
El Workspace del paciente muestra:
- **Resumen:** Contadores y totales (invoices, payments, amounts)
- **Clinical Visits:** Historial de visitas clínicas con tratamientos (paginado, 8/página)
- **Billing Timeline:** Timeline financiero con eventos (paginado, 8/página)
- **Paginación local:** Sin recarga, sin scroll jump, interacción fluida

**Estados vacíos:** Mensajes claros y humanos cuando no hay datos
**Datos reales:** Solo información de base de datos, sin ejemplos ficticios

## Próxima fase prevista
Pendiente de definición por el usuario

## Regla para el asistente
NO avanzar de fase sin confirmación explícita.
NO reescribir código ya validado.
