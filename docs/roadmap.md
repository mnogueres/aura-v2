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

Estado: ⏳ PENDIENTE  
Opciones:
- OpenAPI / Swagger
- Postman collection generada desde contrato
- Validación automática contra tests

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

## FASE 14 — Frontend

Estado: ⏳ PENDIENTE
Ideas:
- Vistas según aura-rules.md
- Integración con API v1
- Layout cápsula desktop / full mobile

---

## Reglas
- Ninguna fase se inicia sin confirmación explícita
- Nada se elimina si tiene tests en verde
- La arquitectura manda sobre la velocidad
