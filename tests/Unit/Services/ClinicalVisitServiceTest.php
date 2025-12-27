<?php

namespace Tests\Unit\Services;

use App\Events\Clinical\VisitRecorded;
use App\Events\Clinical\VisitUpdated;
use App\Events\Clinical\VisitRemoved;
use App\Events\Clinical\TreatmentRecorded;
use App\Models\Visit;
use App\Models\VisitTreatment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Services\ClinicalVisitService;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ClinicalVisitServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClinicalVisitService $service;
    private Clinic $clinic;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'dni' => '12345678A',
            'address' => 'Test Address',
            'phone' => '123456789',
        ]);

        $this->patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dni' => '87654321B',
            'email' => 'john@example.com',
            'phone' => '987654321',
        ]);

        app()->instance('currentClinicId', $this->clinic->id);

        $this->service = app(ClinicalVisitService::class);
    }

    public function test_creates_visit_successfully(): void
    {
        Event::fake();

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => null,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
            'summary' => 'Paciente refiere dolor',
        ];

        $visit = $this->service->createVisit($visitData);

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertEquals($this->clinic->id, $visit->clinic_id);
        $this->assertEquals($this->patient->id, $visit->patient_id);
        $this->assertEquals('Primera visita', $visit->visit_type);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_emits_visit_recorded_event(): void
    {
        Event::fake();

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => null,
            'occurred_at' => now(),
            'visit_type' => 'Revisión',
            'summary' => null,
        ];

        $this->service->createVisit($visitData);

        Event::assertDispatched(VisitRecorded::class, function ($event) {
            return $event->payload['clinic_id'] === $this->clinic->id
                && $event->payload['patient_id'] === $this->patient->id
                && $event->payload['visit_type'] === 'Revisión';
        });
    }

    public function test_creates_visit_with_treatments(): void
    {
        Event::fake();

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => null,
            'occurred_at' => now(),
            'visit_type' => 'Tratamiento',
            'summary' => null,
        ];

        $treatments = [
            [
                'type' => 'Empaste',
                'tooth' => '16',
                'amount' => 65.00,
                'notes' => 'Composite clase II',
            ],
            [
                'type' => 'Limpieza',
                'tooth' => null,
                'amount' => 45.00,
                'notes' => null,
            ],
        ];

        $visit = $this->service->createVisit($visitData, $treatments);

        $this->assertCount(2, $visit->treatments);
        $this->assertDatabaseHas('visit_treatments', [
            'visit_id' => $visit->id,
            'type' => 'Empaste',
            'tooth' => '16',
        ]);
        $this->assertDatabaseHas('visit_treatments', [
            'visit_id' => $visit->id,
            'type' => 'Limpieza',
        ]);
    }

    public function test_emits_treatment_recorded_events(): void
    {
        Event::fake();

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => null,
            'occurred_at' => now(),
        ];

        $treatments = [
            ['type' => 'Empaste', 'tooth' => '16', 'amount' => 65.00],
            ['type' => 'Limpieza', 'tooth' => null, 'amount' => 45.00],
        ];

        $this->service->createVisit($visitData, $treatments);

        Event::assertDispatched(TreatmentRecorded::class, 2);
        Event::assertDispatched(TreatmentRecorded::class, function ($event) {
            return $event->payload['type'] === 'Empaste'
                && $event->payload['tooth'] === '16';
        });
    }

    public function test_throws_exception_when_clinic_id_missing(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('clinic_id is required');

        $visitData = [
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ];

        $this->service->createVisit($visitData);
    }

    public function test_throws_exception_when_patient_id_missing(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('patient_id is required');

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'occurred_at' => now(),
        ];

        $this->service->createVisit($visitData);
    }

    public function test_throws_exception_when_occurred_at_missing(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('occurred_at is required');

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
        ];

        $this->service->createVisit($visitData);
    }

    public function test_throws_exception_when_occurred_at_invalid(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('occurred_at must be a valid date');

        $visitData = [
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => 'invalid-date',
        ];

        $this->service->createVisit($visitData);
    }

    // ===== FASE 20.6: Update Visit tests =====

    public function test_updates_visit_successfully(): void
    {
        Event::fake();

        // Create a visit first
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
            'summary' => 'Resumen inicial',
        ]);

        Event::fake(); // Reset events

        // Update the visit
        $updatedVisit = $this->service->updateVisit($visit->id, [
            'visit_type' => 'Revisión',
            'summary' => 'Resumen actualizado',
        ]);

        $this->assertEquals('Revisión', $updatedVisit->visit_type);
        $this->assertEquals('Resumen actualizado', $updatedVisit->summary);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'visit_type' => 'Revisión',
            'summary' => 'Resumen actualizado',
        ]);
    }

    public function test_emits_visit_updated_event(): void
    {
        Event::fake();

        // Create a visit first
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
            'summary' => 'Resumen inicial',
        ]);

        Event::fake(); // Reset events

        // Update the visit
        $this->service->updateVisit($visit->id, [
            'visit_type' => 'Revisión',
            'summary' => 'Resumen actualizado',
        ]);

        Event::assertDispatched(VisitUpdated::class, function ($event) use ($visit) {
            return $event->payload['visit_id'] === $visit->id
                && $event->payload['clinic_id'] === $this->clinic->id
                && $event->payload['patient_id'] === $this->patient->id
                && $event->payload['visit_type'] === 'Revisión'
                && $event->payload['summary'] === 'Resumen actualizado';
        });
    }

    public function test_update_visit_throws_exception_when_visit_not_found(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Visit not found');

        $this->service->updateVisit('non-existent-uuid', [
            'visit_type' => 'Revisión',
        ]);
    }

    public function test_update_visit_throws_exception_when_visit_deleted(): void
    {
        Event::fake();

        // Create a visit and soft-delete it
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);

        $visit->delete();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot update deleted visit');

        $this->service->updateVisit($visit->id, [
            'visit_type' => 'Revisión',
        ]);
    }

    // ===== FASE 20.6: Remove Visit tests =====

    public function test_removes_visit_successfully(): void
    {
        Event::fake();

        // Create a visit without treatments
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);

        Event::fake(); // Reset events

        // Remove the visit
        $this->service->removeVisit($visit->id);

        // Visit should be soft-deleted
        $this->assertSoftDeleted('visits', [
            'id' => $visit->id,
        ]);
    }

    public function test_emits_visit_removed_event(): void
    {
        Event::fake();

        // Create a visit without treatments
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);

        Event::fake(); // Reset events

        // Remove the visit
        $this->service->removeVisit($visit->id);

        Event::assertDispatched(VisitRemoved::class, function ($event) use ($visit) {
            return $event->payload['visit_id'] === $visit->id
                && $event->payload['clinic_id'] === $this->clinic->id
                && $event->payload['patient_id'] === $this->patient->id;
        });
    }

    public function test_remove_visit_throws_exception_when_visit_has_treatments(): void
    {
        Event::fake();

        // Create a visit with treatments
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Tratamiento',
        ], [
            [
                'type' => 'Empaste',
                'tooth' => '16',
                'amount' => 65.00,
            ],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot remove visit with associated treatments');

        $this->service->removeVisit($visit->id);
    }

    public function test_remove_visit_throws_exception_when_visit_not_found(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Visit not found');

        $this->service->removeVisit('non-existent-uuid');
    }

    public function test_remove_visit_throws_exception_when_visit_already_deleted(): void
    {
        Event::fake();

        // Create a visit and soft-delete it
        $visit = $this->service->createVisit([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);

        $visit->delete();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Visit already deleted');

        $this->service->removeVisit($visit->id);
    }
}
