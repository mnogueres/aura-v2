# Aura — Project Status

## Última sesión
Fecha: 2025-12-28
Estado: FASE 20.2-20.7 completadas - Workspace CQRS + Treatment Catalog + Auto-cálculo de importe

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
- FASE 20.2: Crear visitas desde Workspace (CQRS flow completo, comandos independientes)
- FASE 20.3: Añadir tratamientos a visitas (AddTreatmentToVisit - comandos independientes)
- FASE 20.4: Actualizar y eliminar tratamientos (UpdateTreatment, RemoveTreatment)
- FASE 20.5: Catálogo de tratamientos (TreatmentDefinition, write model obligatorio)
- FASE 20.6: Actualizar y eliminar visitas (UpdateVisit, RemoveVisit + UX sync fixes)
- FASE 20.7: Gestión UI de catálogo de tratamientos (CRUD completo, Aura design, inline editing)
- FASE 20.X: Auto-cálculo de importe por piezas (amount = base_price × teeth_count)

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

## FASE 20 — Workspace CQRS completo + Treatment Catalog
**Fecha:** 2025-12-27/28
**Estado:** ✅ COMPLETADA

### Objetivo
Implementar flujo CQRS completo para gestión de visitas y tratamientos desde el Workspace, transformándolo de read-only a operativo con capacidad de escritura.

### FASE 20.2 — Crear visitas desde Workspace
**Implementación:**
- Comando `CreateVisit` con CQRS flow completo
- Evento `clinical.visit.created` emitido post-commit
- Modal UI para crear visitas con HTMX
- Proyector actualiza `clinical_visits` (read model)
- Tests de integración para flujo completo

**Arquitectura:**
- Write model: `visits` table
- Read model: `clinical_visits` table
- Separación clara: comandos escriben, proyectores proyectan

### FASE 20.3 — Añadir tratamientos (comandos independientes)
**Implementación:**
- `ClinicalTreatmentService::addTreatmentToVisit()`
- Evento `clinical.treatment.added`
- Modal UI con HTMX para añadir tratamientos a visitas existentes
- Write model: `visit_treatments`
- Read model: `clinical_treatments`

**Flujo canónico:**
```
User → Controller → Service → Write Model → Event → Projector → Read Model
```

### FASE 20.4 — Actualizar y eliminar tratamientos
**Implementación:**
- `ClinicalTreatmentService::updateTreatment()`
- `ClinicalTreatmentService::removeTreatmentFromVisit()`
- Eventos: `clinical.treatment.updated`, `clinical.treatment.removed`
- Edición inline con Aura design (sin confirmaciones de navegador)
- Soft delete en write model, hard delete en read model

**UX Fix:**
- HTMX Out-of-Band swaps para sincronizar header de visita
- Actualización automática de `treatments_count`
- Sin recargas de página, flujo completamente asíncrono

### FASE 20.5 — Catálogo de tratamientos (write model)
**Implementación:**
- Modelo `TreatmentDefinition` (write model, source of truth)
- Campos: `name`, `default_price`, `active`
- CRUD completo via `ClinicalTreatmentCatalogService`
- Eventos: `treatment_definition.created/updated/deactivated/deleted`
- Catálogo es OBLIGATORIO: no se permite entrada manual de tratamientos
- Seeder con 25 tratamientos dentales comunes

**Regla de negocio:**
- El catálogo sugiere precios, pero el profesional decide el importe final por visita
- Snapshot pattern: `type` y `amount` se copian a `visit_treatments` (no FK)

### FASE 20.6 — Actualizar y eliminar visitas
**Implementación:**
- `ClinicalVisitService::updateVisit()`
- `ClinicalVisitService::removeVisit()`
- Eventos: `clinical.visit.updated`, `clinical.visit.removed`
- Cascada: eliminar visita → elimina todos sus tratamientos
- UX sync fix: actualización del header tras cualquier operación

### FASE 20.7 — Gestión UI de catálogo de tratamientos
**Implementación:**
- Vista `/workspace/treatments` con listado completo
- Inline editing estilo Aura (hover actions, sin modales pesados)
- Crear, editar, desactivar tratamientos
- Borrado condicional: solo si nunca se usó en ninguna visita
- HTMX para todas las operaciones (sin recargas)

**Características:**
- Búsqueda/filtrado en tiempo real
- Estados: activo/inactivo
- Validación de uso antes de eliminar
- Feedback visual inmediato (success/error)

### FASE 20.X — Auto-cálculo de importe por piezas
**Fecha:** 2025-12-28
**Estado:** ✅ COMPLETADA

**Objetivo:**
Calcular automáticamente el importe basándose en el número de piezas dentales indicadas.

**Fórmula:**
```
importe_sugerido = precio_base × max(1, número_de_piezas)
```

**Implementación:**
- JavaScript con event delegation (compatible con HTMX)
- Funciona en modal de añadir tratamiento
- Funciona en edición inline de tratamientos
- Cuenta piezas separadas por coma (ej: "16,23,57" → 3 piezas)
- Respeta edición manual: si usuario edita importe, Aura deja de intervenir
- Al modificar piezas → recalcula automáticamente (resetea flag manual)

**Principio UX:**
> "El catálogo sugiere. La visita decide. El profesional manda."

**Fix crítico incluido:**
- `treatment_definition_id` no se sincronizaba al read model
- Agregado al evento `TreatmentAdded` payload
- Actualizado `ClinicalTreatmentProjector` para copiar campo
- Migración para agregar columna en `clinical_treatments`
- Relación `treatmentDefinition()` en modelo `ClinicalTreatment`
- Eager loading en repositorio (previene N+1 queries)
- Migración de datos: 7 tratamientos existentes actualizados

**Archivos modificados:**
- `TreatmentAdded.php` - payload extendido
- `ClinicalTreatmentService.php` - emit con treatment_definition_id
- `ClinicalTreatmentProjector.php` - copia campo a read model
- `ClinicalTreatment.php` - relación y fillable
- `ClinicalTreatmentRepository.php` - eager loading
- `_new_treatment_modal.blade.php` - JS auto-cálculo
- `_visit_treatment_item.blade.php` - JS auto-cálculo inline
- Migration: `add_treatment_definition_id_to_clinical_treatments_table`

**Resultado:**
- Usuario selecciona "Endodoncia (150€)"
- Escribe piezas: "16,23,57" → importe se actualiza a 450€
- Borra una pieza: "16,23" → importe se actualiza a 300€
- Si edita importe manualmente a 280€ → Aura respeta y no recalcula
- Si luego modifica piezas → vuelve a recalcular

### Arquitectura CQRS consolidada

**Write Side:**
- `visits` table (source of truth para visitas)
- `visit_treatments` table (source of truth para tratamientos)
- `treatment_definitions` table (catálogo maestro)

**Read Side:**
- `clinical_visits` table (proyección optimizada para lectura)
- `clinical_treatments` table (proyección con relaciones precargadas)
- `clinical_treatment_definitions` table (proyección del catálogo)

**Event Flow:**
```
Command → Service → Write Model → Event (outbox) → Consumer → Projector → Read Model
```

**Ventajas logradas:**
- Escritura y lectura totalmente desacopladas
- Proyecciones optimizadas para queries específicas
- Idempotencia garantizada
- Posibilidad de replay de eventos
- Historial completo en outbox
- Tests independientes de read/write

### Tests implementados
- `ClinicalTreatmentCatalogServiceTest.php` - 24 tests unitarios (CRUD catálogo)
- `TreatmentCatalogIntegrationTest.php` - Tests de integración eventos
- `TreatmentCatalogWorkspaceTest.php` - Tests UI workspace
- Todos los tests en verde ✅

## Próxima fase prevista
Pendiente de definición por el usuario

## Regla para el asistente
NO avanzar de fase sin confirmación explícita.
NO reescribir código ya validado.
