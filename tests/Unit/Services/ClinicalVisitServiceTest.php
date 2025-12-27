<?php

namespace Tests\Unit\Services;

use App\Events\Clinical\VisitRecorded;
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
}
