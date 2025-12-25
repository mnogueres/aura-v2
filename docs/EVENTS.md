# Domain Events Taxonomy

This document defines the complete taxonomy of domain events in Aura v2.

## Event Envelope Structure

All events share this common envelope:

```json
{
  "event": "billing.payment.recorded",
  "occurred_at": "2025-12-24T12:34:56Z",
  "request_id": "uuid",
  "user_id": 1,
  "clinic_id": 2,
  "payload": { ... }
}
```

## CRM Domain

### crm.patient.created

**Class:** `App\Events\CRM\PatientCreated`

**Emitted when:** POST /patients → 201

**Emitted from:** `App\Services\PatientService::create()`

**Payload:**
```json
{
  "patient_id": 123
}
```

---

## Billing Domain

### billing.invoice.created

**Class:** `App\Events\Billing\InvoiceCreated`

**Emitted when:** POST /invoices → 201

**Emitted from:** `App\Services\InvoiceService::create()`

**Payload:**
```json
{
  "invoice_id": 456,
  "status": "draft"
}
```

### billing.invoice.issued

**Class:** `App\Events\Billing\InvoiceIssued`

**Emitted when:** Invoice status transitions to "issued"

**Emitted from:** `App\Services\InvoiceService::create()` (when created as "issued")

**Payload:**
```json
{
  "invoice_id": 456,
  "status": "issued"
}
```

### billing.invoice.paid

**Class:** `App\Events\Billing\InvoicePaid`

**Emitted when:** Invoice balance_due reaches 0

**Emitted from:** `App\Services\InvoiceService::checkPaidStatus()`

**Payload:**
```json
{
  "invoice_id": 456,
  "status": "paid"
}
```

### billing.payment.recorded

**Class:** `App\Events\Billing\PaymentRecorded`

**Emitted when:** POST /payments → 201

**Emitted from:** `App\Services\PaymentService::create()`

**Payload:**
```json
{
  "payment_id": 789,
  "invoice_id": 456,
  "amount": 50.00
}
```

**Note:** `invoice_id` can be null for unlinked payments.

### billing.payment.applied

**Class:** `App\Events\Billing\PaymentApplied`

**Emitted when:** Payment is associated with an invoice (invoice_id is not null)

**Emitted from:** `App\Services\PaymentService::create()` (when invoice_id is not null)

**Payload:**
```json
{
  "payment_id": 789,
  "invoice_id": 456,
  "amount": 50.00
}
```

### billing.payment.unlinked

**Class:** `App\Events\Billing\PaymentUnlinked`

**Emitted when:** Payment is created without an invoice (invoice_id is null)

**Emitted from:** `App\Services\PaymentService::create()` (when invoice_id is null)

**Payload:**
```json
{
  "payment_id": 789,
  "invoice_id": null,
  "amount": 50.00
}
```

---

## Platform Domain

### platform.rate_limited

**Class:** `App\Events\Platform\RateLimited`

**Emitted when:** 429 Too Many Requests response is returned

**Emitted from:** Rate limiter handler (future implementation)

**Payload:**
```json
{
  "endpoint": "/api/v1/payments",
  "method": "POST"
}
```

### platform.idempotency.replayed

**Class:** `App\Events\Platform\IdempotencyReplayed`

**Emitted when:** Idempotency key is reused with identical body (cached response returned)

**Emitted from:** `App\Http\Middleware\EnsureIdempotency`

**Payload:**
```json
{
  "endpoint": "/api/v1/payments",
  "method": "POST"
}
```

### platform.idempotency.conflict

**Class:** `App\Events\Platform\IdempotencyConflict`

**Emitted when:** Idempotency key is reused with different body (409 Conflict)

**Emitted from:** `App\Http\Middleware\EnsureIdempotency`

**Payload:**
```json
{
  "endpoint": "/api/v1/payments",
  "method": "POST"
}
```

---

## Event Taxonomy Summary

| Event | Domain | Class |
|-------|--------|-------|
| `crm.patient.created` | CRM | `App\Events\CRM\PatientCreated` |
| `billing.invoice.created` | Billing | `App\Events\Billing\InvoiceCreated` |
| `billing.invoice.issued` | Billing | `App\Events\Billing\InvoiceIssued` |
| `billing.invoice.paid` | Billing | `App\Events\Billing\InvoicePaid` |
| `billing.payment.recorded` | Billing | `App\Events\Billing\PaymentRecorded` |
| `billing.payment.applied` | Billing | `App\Events\Billing\PaymentApplied` |
| `billing.payment.unlinked` | Billing | `App\Events\Billing\PaymentUnlinked` |
| `platform.rate_limited` | Platform | `App\Events\Platform\RateLimited` |
| `platform.idempotency.replayed` | Platform | `App\Events\Platform\IdempotencyReplayed` |
| `platform.idempotency.conflict` | Platform | `App\Events\Platform\IdempotencyConflict` |

**Total Events:** 10

---

## Implementation Notes

### Event Emission (PHASE 13.3 - COMPLETED)

- ✅ Events are emitted via `EventService::emit()`
- ✅ Emission occurs **after DB commit** (using `DB::afterCommit()`)
- ✅ Controllers delegate to services (zero business logic in controllers)
- ✅ Platform events emitted directly from middleware
- ❌ No event listeners registered yet
- ❌ No event persistence (outbox pattern - future phase)
- ❌ No queue processing configured
- ❌ No external integrations

### EventService Architecture

All domain events (CRM, Billing) are emitted through `EventService::emit()`:

```php
use App\Services\EventService;
use App\Events\CRM\PatientCreated;

$this->eventService->emit(
    new PatientCreated(patient_id: $patient->id)
);
```

**Key guarantees:**
- Events emitted only after successful DB commit
- Never emitted on validation errors
- Never emitted on failed transactions
- Never emitted on idempotency replays
- Never throws exceptions that break request flow

### Event Metadata (Auto-captured)

Every event automatically captures:
- `request_id` from X-Request-Id header
- `user_id` from authenticated user
- `clinic_id` from current clinic context
- `occurred_at` timestamp in ISO 8601 format

### Testing

Event emission is fully tested with contract tests:
- See `tests/Feature/Events/DomainEventEmissionTest.php`
- Uses `Event::fake()` and `Event::assertDispatched()`
- Verifies events are emitted exactly once
- Verifies events are NOT emitted on replay/validation errors
