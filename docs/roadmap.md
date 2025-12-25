# Aura — Roadmap

Este documento define las siguientes fases previstas.
No implica que estén aprobadas automáticamente.

---

## FASE 11.x — Hardening API (ACTUAL)

### 11.1 — Error handling unificado
Estado: ✅ COMPLETADA  
- Envelope único para errores
- request_id
- Handler centralizado
- Tests de contrato

### 11.2 — Rate limiting inteligente
Estado: ✅ COMPLETADA  
- Límites por user + clinic + IP
- Throttles diferenciados (read / write / payments)
- Tests incluidos

### 11.3 — Idempotencia
Estado: ✅ COMPLETADA
- Header Idempotency-Key obligatorio en POST
- Middleware EnsureIdempotency implementado
- Almacenamiento persistente en DB (TTL 24h)
- Auto-limpieza de registros expirados
- Tests incluidos (retry safe, conflictos, TTL)

---

## FASE 12 — Contrato externo (API pública)

Estado: ✅ COMPLETADA

### 12 — OpenAPI Specification
Estado: ✅ COMPLETADA
- OpenAPI 3.0.3 spec completa (docs/openapi/openapi.yaml)
- Documenta 4 endpoints Workspace
- Schemas, ejemplos, y respuestas de error
- Arquitectura CQRS/Event-driven documentada

### 12.x — Swagger UI
Estado: ✅ COMPLETADA
- Integración Swagger UI (dev-only)
- Accesible en /docs/api (solo local)
- Assets compilados con Vite
- Sin exposición en producción

---

## FASE 13 — Event-Driven Architecture

Estado: ✅ COMPLETADA

### 13.1 — Taxonomía de eventos
Estado: ✅ COMPLETADA
- 10 eventos de dominio definidos (CRM, Billing, Platform)
- Documentación en docs/EVENTS.md
- Nomenclatura estándar: domain.entity.action

### 13.2 — Event Classes
Estado: ✅ COMPLETADA
- Clases de eventos en app/Events/{Domain}
- Envelope estándar (event, occurred_at, request_id, user_id, clinic_id, payload)
- ShouldBroadcast implementado

### 13.3 — EventService
Estado: ✅ COMPLETADA
- Servicio centralizado para emisión de eventos
- Emisión post-commit (DB::afterCommit)
- Metadatos auto-capturados
- Tests de contrato

### 13.4 — Outbox Pattern
Estado: ✅ COMPLETADA
- Tabla outbox_events (pending/processed/failed)
- Logging transaccional de eventos
- TTL y auto-limpieza
- Idempotencia garantizada

### 13.5 — Workers/Consumers
Estado: ✅ COMPLETADA
- OutboxEventConsumer service (batch processing)
- ProcessOutboxEvents job (queue)
- Command artisan outbox:process
- Reintentos (max 5) y locking
- Tests completos (10/10 pasando)

---

## FASE 14 — Projections / Read Models

Estado: ✅ COMPLETADA

### 14.1 — Patient Timeline
Estado: ✅ COMPLETADA
- Timeline cronológico de eventos por paciente
- Read model derivado de eventos CRM y Billing
- Tests incluidos (12/12 pasando)

### 14.2 — Patient Summary
Estado: ✅ COMPLETADA
- Contadores y totales agregados por paciente
- Campos: invoices_count, payments_count, total_invoiced, total_paid
- Actualización incremental (increment/decrement)
- Tests incluidos (9/9 pasando)

### 14.3 — Billing Timeline
Estado: ✅ COMPLETADA
- Timeline financiero con montos y referencias
- Eventos de invoices y payments
- Tests incluidos (8/8 pasando)

### 14.4 — Audit Trail (Platform)
Estado: ✅ COMPLETADA
- Audit técnico para eventos de plataforma
- Category y severity por tipo de evento
- Actor detection (system/user)
- Tests incluidos (14/14 pasando)

---

## FASE 15 — Workspace (Read-only)

Estado: ✅ COMPLETADA

### 15.1 — Workspace API Endpoints
Estado: ✅ COMPLETADA
- GET /api/v1/workspace/patients/{patientId}/summary
- GET /api/v1/workspace/patients/{patientId}/timeline
- GET /api/v1/workspace/patients/{patientId}/billing
- GET /api/v1/workspace/audit

### 15.2 — Controllers & Repositories
Estado: ✅ COMPLETADA
- PatientSummaryController + Repository
- PatientTimelineController
- BillingTimelineController
- AuditTrailController

### 15.3 — Workspace UI Integration
Estado: ✅ COMPLETADA
- Vista PatientWorkspace con Aura design system
- Componentes Blade reutilizables
- Timeline con humanización de eventos
- Paginación implementada

---

## FASE 16 — Modelo Clínico: Visitas y Tratamientos

**Tipo:** 🔒 DISEÑO EXCLUSIVAMENTE
Estado: ✅ COMPLETADA

### Objetivo
Definir el modelo clínico conceptual respondiendo:
- ¿Qué es una visita clínica en Aura?
- ¿Qué es un tratamiento y cómo se relaciona con una visita?
- ¿Qué debe ver el usuario en el Workspace?
- ¿Qué NO debe verse nunca (eventos técnicos)?

### Entregables
- ✅ Documento `docs/CLINICAL_MODEL.md` creado
- ✅ Definición de conceptos clínicos (Visita, Tratamiento)
- ✅ Modelo conceptual no técnico
- ✅ Timeline clínico humanizado especificado
- ✅ Roles y expectativas definidos (Auxiliar, Profesional, Contable)
- ✅ Relación con arquitectura event-driven explicada

### Prohibiciones cumplidas
- ❌ NO se crearon migrations
- ❌ NO se crearon modelos Eloquent
- ❌ NO se crearon eventos
- ❌ NO se crearon controllers
- ❌ NO se crearon vistas
- ❌ NO se escribió código de implementación

**Siguiente paso:** Validación conceptual antes de implementación

---

## API v1 — Congelación de contrato

**Fecha:** 2025-12-25
**Estado:** ✅ CONGELADA

API v1 está congelada como contrato estable:
- No se eliminarán endpoints existentes
- No se cambiarán contratos (request/response)
- No se romperán schemas
- Cambios compatibles permitidos (nuevos endpoints, campos opcionales)
- Cambios incompatibles requieren /v2

**Documentación:** Ver `docs/API_VERSIONING.md`

---

## Reglas
- Ninguna fase se inicia sin confirmación explícita
- Nada se elimina si tiene tests en verde
- La arquitectura manda sobre la velocidad
- API v1 es un contrato vivo: el código debe cumplir el OpenAPI spec
