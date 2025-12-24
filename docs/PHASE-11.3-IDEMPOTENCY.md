# FASE 11.3 — Idempotency (Design Locked)

## Objective
Guarantee that POST write operations are idempotent.
Same Idempotency-Key + same endpoint + same user + same body
must return the exact same response.

Critical endpoints:
- POST /api/v1/payments (financial)
- POST /api/v1/invoices
- POST /api/v1/patients

## Rules
- Idempotency-Key header is REQUIRED on POST write endpoints
- Missing key → 400 Bad Request
- Same key + different body → 409 Conflict
- Validation errors are NOT stored
- 5xx errors are NOT stored

## Scope
Idempotency is scoped by:
- user_id
- clinic_id
- endpoint
- HTTP method

## Storage
Persistent DB table (no cache, no Redis).

Table: idempotency_keys
Fields:
- id
- key (unique)
- user_id
- clinic_id
- endpoint
- method
- request_hash (sha256)
- response_status
- response_body (json)
- created_at
- expires_at (TTL: 24h)

## Flow
1. Extract Idempotency-Key
2. Lookup existing key
3. If exists:
   - same hash → return stored response
   - different hash → 409
4. If not exists:
   - execute domain logic
   - store response
   - return response

## Implementation constraints
- Controllers MUST remain clean
- Logic must live in middleware or service
- Apply only to POST write routes
- Envelope format must be preserved

## Tests required
- Safe retry (same request twice → 1 record)
- Conflict on different payload
- Missing key → 400
- TTL expiration allows re-execution
