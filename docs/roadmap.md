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

## FASE 13 — Observabilidad

Estado: ⏳ PENDIENTE  
Ideas:
- Logging estructurado
- Correlation ID (request_id)
- Métricas básicas (latencia, errores)

---

## Reglas
- Ninguna fase se inicia sin confirmación explícita
- Nada se elimina si tiene tests en verde
- La arquitectura manda sobre la velocidad
