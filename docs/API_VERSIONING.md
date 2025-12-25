# Aura API — Versioning & Stability Policy

## API v1 — Frozen

**Estado:** Congelada
**Fecha de congelación:** 2025-12-25
**Versión:** `/api/v1`

### Garantías de estabilidad

API v1 está congelada como contrato estable. Esto significa:

- **No se eliminarán endpoints** existentes
- **No se cambiarán contratos existentes** (request/response)
- **No se romperán schemas** (tipos, campos requeridos)
- **No se cambiará el significado de campos** existentes
- **No se modificarán convenciones** (envelope, pagination, errors)

### Qué incluye API v1

**Workspace Read-Only Endpoints:**
- `GET /api/v1/workspace/patients/{patientId}/summary`
- `GET /api/v1/workspace/patients/{patientId}/timeline`
- `GET /api/v1/workspace/patients/{patientId}/billing`
- `GET /api/v1/workspace/audit`

**Convenciones API:**
- Envelope format: `{ data, meta }` para éxito
- Error format: `{ data: null, error: { code, message, details }, meta }`
- Pagination: 25 items/página (fijo)
- Rate limiting: 120 req/min (lecturas), 60 req/min (escrituras)
- Idempotency: Header `Idempotency-Key` para operaciones POST

**Seguridad:**
- Bearer authentication (JWT/session token)
- Multi-tenant scoping (clinic_id desde auth context)

**Modelos de lectura (proyecciones):**
- PatientSummary
- PatientTimeline
- BillingTimeline
- AuditTrail

### Qué NO incluye API v1

API v1 **no cubre**:
- Endpoints de escritura clínica (tratamientos, evoluciones)
- Mutaciones del Workspace
- Gestión de citas
- Gestión de inventario
- Funcionalidad administrativa avanzada
- Reportes complejos

Estas capacidades serán añadidas como **nuevos endpoints compatibles** o en **versiones futuras** según corresponda.

## Evolución futura

### Cambios permitidos en v1 (compatibles):

- **Añadir nuevos endpoints** bajo `/api/v1/*`
- **Añadir campos opcionales** a respuestas existentes
- **Añadir nuevos códigos de error** (manteniendo los existentes)
- **Mejorar performance** sin cambiar contratos
- **Añadir nuevos valores enum** (si no rompen validación)

### Cambios NO permitidos en v1 (incompatibles):

- Eliminar endpoints
- Eliminar campos de respuestas
- Cambiar tipos de datos
- Modificar validación de requests existentes
- Cambiar el significado de campos
- Cambiar estructura del envelope
- Romper paginación existente

### Cambios incompatibles → API v2

Cualquier cambio incompatible requiere:
- Nueva versión: `/api/v2`
- Deprecation notice en v1 (mínimo 6 meses)
- Documentación de migración
- Soporte paralelo durante período de transición

### Nuevos dominios

Nuevas capacidades de negocio que no existen en v1:
- Pueden añadirse como nuevos endpoints en v1 si son compatibles
- Pueden requerir v2 si necesitan cambios arquitecturales

## Contrato vivo

La especificación OpenAPI en `docs/openapi/openapi.yaml` es la fuente de verdad del contrato v1.

Cualquier discrepancia entre código y OpenAPI debe resolverse actualizando el **código** para cumplir el contrato, no al revés.

## Documentación

- **OpenAPI spec:** `docs/openapi/openapi.yaml`
- **Swagger UI:** `http://localhost:8000/docs/api` (solo dev)
- **Arquitectura:** `docs/EVENTS.md`

## Contacto

Para consultas sobre compatibilidad o evolución de la API, revisar este documento y la especificación OpenAPI antes de realizar cambios.
