<?php

namespace Tests\Feature\Projections;

use App\Events\Platform\IdempotencyConflict;
use App\Events\Platform\IdempotencyReplayed;
use App\Events\Platform\RateLimited;
use App\Models\AuditTrail;
use App\Models\Clinic;
use App\Repositories\AuditTrailRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailProjectionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private AuditTrailRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::create(['name' => 'Test Clinic']);
        $this->repository = app(AuditTrailRepository::class);

        // Set current clinic context
        app()->instance('currentClinicId', $this->clinic->id);
    }

    public function test_rate_limited_event_creates_audit_trail_entry(): void
    {
        // Emit event
        event(new RateLimited(
            endpoint: '/api/v1/patients',
            method: 'POST',
            request_id: 'req-123',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert audit trail entry exists
        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertCount(1, $entries);
        $this->assertEquals('platform.rate_limited', $entries->first()->event_name);
        $this->assertEquals('security', $entries->first()->category);
        $this->assertEquals('warning', $entries->first()->severity);
        $this->assertEquals('user', $entries->first()->actor_type);
        $this->assertEquals(1, $entries->first()->actor_id);
        $this->assertIsArray($entries->first()->context);
        $this->assertEquals('/api/v1/patients', $entries->first()->context['endpoint']);
        $this->assertEquals('POST', $entries->first()->context['method']);
    }

    public function test_idempotency_replayed_event_creates_audit_trail_entry(): void
    {
        // Emit event
        event(new IdempotencyReplayed(
            endpoint: '/api/v1/invoices',
            method: 'POST',
            request_id: 'req-456',
            user_id: null,
            clinic_id: $this->clinic->id
        ));

        // Assert audit trail entry exists
        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertCount(1, $entries);
        $this->assertEquals('platform.idempotency.replayed', $entries->first()->event_name);
        $this->assertEquals('platform', $entries->first()->category);
        $this->assertEquals('info', $entries->first()->severity);
        $this->assertEquals('system', $entries->first()->actor_type);
        $this->assertNull($entries->first()->actor_id);
    }

    public function test_idempotency_conflict_event_creates_audit_trail_entry(): void
    {
        // Emit event
        event(new IdempotencyConflict(
            endpoint: '/api/v1/payments',
            method: 'POST',
            request_id: 'req-789',
            user_id: 2,
            clinic_id: $this->clinic->id
        ));

        // Assert audit trail entry exists
        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertCount(1, $entries);
        $this->assertEquals('platform.idempotency.conflict', $entries->first()->event_name);
        $this->assertEquals('security', $entries->first()->category);
        $this->assertEquals('error', $entries->first()->severity);
        $this->assertEquals('user', $entries->first()->actor_type);
        $this->assertEquals(2, $entries->first()->actor_id);
    }

    public function test_actor_type_is_system_when_user_id_is_null(): void
    {
        // Emit event without user_id
        event(new RateLimited(
            endpoint: '/api/v1/test',
            method: 'GET',
            request_id: 'req-system',
            user_id: null,
            clinic_id: $this->clinic->id
        ));

        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertEquals('system', $entries->first()->actor_type);
        $this->assertNull($entries->first()->actor_id);
    }

    public function test_actor_type_is_user_when_user_id_is_provided(): void
    {
        // Emit event with user_id
        event(new RateLimited(
            endpoint: '/api/v1/test',
            method: 'GET',
            request_id: 'req-user',
            user_id: 5,
            clinic_id: $this->clinic->id
        ));

        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertEquals('user', $entries->first()->actor_type);
        $this->assertEquals(5, $entries->first()->actor_id);
    }

    public function test_context_is_persisted_correctly(): void
    {
        // Emit event
        event(new RateLimited(
            endpoint: '/api/v1/complex/endpoint',
            method: 'PUT',
            request_id: 'req-context',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $entries = $this->repository->getForClinic($this->clinic->id);
        $context = $entries->first()->context;

        $this->assertIsArray($context);
        $this->assertArrayHasKey('endpoint', $context);
        $this->assertArrayHasKey('method', $context);
        $this->assertEquals('/api/v1/complex/endpoint', $context['endpoint']);
        $this->assertEquals('PUT', $context['method']);
    }

    public function test_duplicate_events_are_not_created_on_replay(): void
    {
        $requestId = 'req-duplicate-test';

        // Emit same event twice (simulating replay)
        event(new RateLimited(
            endpoint: '/api/v1/test',
            method: 'POST',
            request_id: $requestId,
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new RateLimited(
            endpoint: '/api/v1/test',
            method: 'POST',
            request_id: $requestId,
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert only one audit trail entry exists
        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertCount(1, $entries, 'Audit trail should not contain duplicate entries for the same event');
    }

    public function test_projection_does_not_write_to_domain_tables(): void
    {
        // Count domain tables before
        $initialClinicCount = Clinic::count();

        // Emit event
        event(new RateLimited(
            endpoint: '/api/v1/test',
            method: 'POST',
            request_id: 'req-domain-test',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert domain tables were not modified
        $this->assertEquals($initialClinicCount, Clinic::count());

        // But audit trail was updated
        $this->assertEquals(1, AuditTrail::count());
    }

    public function test_audit_trail_is_scoped_to_clinic(): void
    {
        $otherClinic = Clinic::create(['name' => 'Other Clinic']);

        // Emit events for both clinics
        event(new RateLimited(
            endpoint: '/api/v1/test1',
            method: 'POST',
            request_id: 'req-clinic-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new RateLimited(
            endpoint: '/api/v1/test2',
            method: 'POST',
            request_id: 'req-clinic-2',
            user_id: 1,
            clinic_id: $otherClinic->id
        ));

        // Assert audit trails are separated
        $entries1 = $this->repository->getForClinic($this->clinic->id);
        $entries2 = $this->repository->getForClinic($otherClinic->id);

        $this->assertCount(1, $entries1);
        $this->assertCount(1, $entries2);
        $this->assertNotEquals($entries1->first()->id, $entries2->first()->id);
    }

    public function test_multiple_event_types_create_audit_entries(): void
    {
        // Emit multiple different events
        event(new RateLimited(
            endpoint: '/api/v1/test1',
            method: 'POST',
            request_id: 'req-multi-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new IdempotencyReplayed(
            endpoint: '/api/v1/test2',
            method: 'POST',
            request_id: 'req-multi-2',
            user_id: null,
            clinic_id: $this->clinic->id
        ));

        event(new IdempotencyConflict(
            endpoint: '/api/v1/test3',
            method: 'POST',
            request_id: 'req-multi-3',
            user_id: 2,
            clinic_id: $this->clinic->id
        ));

        // Assert all events are in audit trail
        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertCount(3, $entries);
        $eventNames = $entries->pluck('event_name')->toArray();
        $this->assertContains('platform.rate_limited', $eventNames);
        $this->assertContains('platform.idempotency.replayed', $eventNames);
        $this->assertContains('platform.idempotency.conflict', $eventNames);
    }

    public function test_source_event_id_is_unique_per_event(): void
    {
        // Emit event
        event(new RateLimited(
            endpoint: '/api/v1/test',
            method: 'POST',
            request_id: 'req-unique',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Get the entry
        $entries = $this->repository->getForClinic($this->clinic->id);

        // Assert source_event_id exists and is unique
        $this->assertCount(1, $entries);
        $this->assertNotNull($entries->first()->source_event_id);
        $this->assertIsString($entries->first()->source_event_id);

        // Verify it's a hash (64 characters for SHA-256)
        $this->assertEquals(64, strlen($entries->first()->source_event_id));
    }

    public function test_get_by_severity_filters_correctly(): void
    {
        // Emit events with different severities
        event(new RateLimited(
            endpoint: '/api/v1/test1',
            method: 'POST',
            request_id: 'req-sev-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new IdempotencyReplayed(
            endpoint: '/api/v1/test2',
            method: 'POST',
            request_id: 'req-sev-2',
            user_id: null,
            clinic_id: $this->clinic->id
        ));

        event(new IdempotencyConflict(
            endpoint: '/api/v1/test3',
            method: 'POST',
            request_id: 'req-sev-3',
            user_id: 2,
            clinic_id: $this->clinic->id
        ));

        // Get by severity
        $warningEntries = $this->repository->getBySeverity('warning', $this->clinic->id);
        $infoEntries = $this->repository->getBySeverity('info', $this->clinic->id);
        $errorEntries = $this->repository->getBySeverity('error', $this->clinic->id);

        $this->assertCount(1, $warningEntries);
        $this->assertCount(1, $infoEntries);
        $this->assertCount(1, $errorEntries);

        $this->assertEquals('platform.rate_limited', $warningEntries->first()->event_name);
        $this->assertEquals('platform.idempotency.replayed', $infoEntries->first()->event_name);
        $this->assertEquals('platform.idempotency.conflict', $errorEntries->first()->event_name);
    }

    public function test_get_by_category_filters_correctly(): void
    {
        // Emit events with different categories
        event(new RateLimited(
            endpoint: '/api/v1/test1',
            method: 'POST',
            request_id: 'req-cat-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new IdempotencyReplayed(
            endpoint: '/api/v1/test2',
            method: 'POST',
            request_id: 'req-cat-2',
            user_id: null,
            clinic_id: $this->clinic->id
        ));

        // Get by category
        $securityEntries = $this->repository->getByCategory('security', $this->clinic->id);
        $platformEntries = $this->repository->getByCategory('platform', $this->clinic->id);

        $this->assertCount(1, $securityEntries);
        $this->assertCount(1, $platformEntries);

        $this->assertEquals('platform.rate_limited', $securityEntries->first()->event_name);
        $this->assertEquals('platform.idempotency.replayed', $platformEntries->first()->event_name);
    }

    public function test_entries_are_ordered_by_occurred_at_desc(): void
    {
        // Emit events with delays
        event(new RateLimited(
            endpoint: '/api/v1/test1',
            method: 'POST',
            request_id: 'req-order-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        sleep(1);

        event(new IdempotencyReplayed(
            endpoint: '/api/v1/test2',
            method: 'POST',
            request_id: 'req-order-2',
            user_id: null,
            clinic_id: $this->clinic->id
        ));

        sleep(1);

        event(new IdempotencyConflict(
            endpoint: '/api/v1/test3',
            method: 'POST',
            request_id: 'req-order-3',
            user_id: 2,
            clinic_id: $this->clinic->id
        ));

        // Assert entries are in reverse chronological order (newest first)
        $entries = $this->repository->getForClinic($this->clinic->id);

        $this->assertCount(3, $entries);
        $this->assertEquals('platform.idempotency.conflict', $entries[0]->event_name);
        $this->assertEquals('platform.idempotency.replayed', $entries[1]->event_name);
        $this->assertEquals('platform.rate_limited', $entries[2]->event_name);
    }
}
