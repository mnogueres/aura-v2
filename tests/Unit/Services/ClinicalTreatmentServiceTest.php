<?php

namespace Tests\Unit\Services;

use App\Services\ClinicalTreatmentService;
use App\Services\EventService;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitTreatment;
use App\Models\EventOutbox;
use App\Events\Clinical\TreatmentAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalTreatmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClinicalTreatmentService $service;
    private Clinic $clinic;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ClinicalTreatmentService::class);

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

        $this->visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);
    }

    public function test_adds_treatment_to_visit_successfully(): void
    {
        $treatmentData = [
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Composite clase II',
        ];

        $treatment = $this->service->addTreatmentToVisit($this->visit->id, $treatmentData);

        $this->assertInstanceOf(VisitTreatment::class, $treatment);
        $this->assertEquals('Empaste', $treatment->type);
        $this->assertEquals('16', $treatment->tooth);
        $this->assertEquals('65.00', $treatment->amount);
        $this->assertEquals('Composite clase II', $treatment->notes);
        $this->assertEquals($this->visit->id, $treatment->visit_id);
        $this->assertEquals($this->clinic->id, $treatment->clinic_id);
        $this->assertEquals($this->patient->id, $treatment->patient_id);
    }

    public function test_emits_treatment_added_event(): void
    {
        $treatmentData = [
            'type' => 'Limpieza',
            'tooth' => null,
            'amount' => '30.00',
            'notes' => null,
        ];

        $this->service->addTreatmentToVisit($this->visit->id, $treatmentData);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment.added',
            'clinic_id' => $this->clinic->id,
            'status' => 'pending',
        ]);

        $outboxEvent = EventOutbox::where('event_name', 'clinical.treatment.added')->first();
        $this->assertNotNull($outboxEvent);
        $this->assertEquals($this->clinic->id, $outboxEvent->payload['clinic_id']);
        $this->assertEquals($this->visit->id, $outboxEvent->payload['visit_id']);
        $this->assertEquals($this->patient->id, $outboxEvent->payload['patient_id']);
        $this->assertEquals('Limpieza', $outboxEvent->payload['type']);
        $this->assertEquals('30.00', $outboxEvent->payload['amount']);
    }

    public function test_requires_type(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('type is required');

        $this->service->addTreatmentToVisit($this->visit->id, [
            'tooth' => '16',
        ]);
    }

    public function test_validates_amount_is_numeric(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('amount must be numeric');

        $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
            'amount' => 'not-a-number',
        ]);
    }

    public function test_validates_amount_is_positive(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('amount must be positive');

        $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
            'amount' => '-50.00',
        ]);
    }

    public function test_throws_exception_for_nonexistent_visit(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Visit not found');

        $this->service->addTreatmentToVisit('non-existent-uuid', [
            'type' => 'Empaste',
        ]);
    }

    public function test_creates_treatment_with_optional_fields_null(): void
    {
        $treatmentData = [
            'type' => 'Consulta',
        ];

        $treatment = $this->service->addTreatmentToVisit($this->visit->id, $treatmentData);

        $this->assertEquals('Consulta', $treatment->type);
        $this->assertNull($treatment->tooth);
        $this->assertNull($treatment->amount);
        $this->assertNull($treatment->notes);
    }

    public function test_treatment_is_persisted_in_database(): void
    {
        $treatmentData = [
            'type' => 'Extracción',
            'tooth' => '38',
            'amount' => '80.00',
            'notes' => 'Muela del juicio inferior derecha',
        ];

        $treatment = $this->service->addTreatmentToVisit($this->visit->id, $treatmentData);

        $this->assertDatabaseHas('visit_treatments', [
            'id' => $treatment->id,
            'visit_id' => $this->visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Extracción',
            'tooth' => '38',
        ]);
    }

    // FASE 20.4: Tests for updateTreatment

    public function test_updates_treatment_successfully(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Original notes',
        ]);

        $updated = $this->service->updateTreatment($treatment->id, [
            'type' => 'Endodoncia',
            'amount' => '150.00',
        ]);

        $this->assertEquals('Endodoncia', $updated->type);
        $this->assertEquals('150.00', $updated->amount);
        $this->assertEquals('16', $updated->tooth); // not updated
        $this->assertEquals('Original notes', $updated->notes); // not updated
    }

    public function test_update_emits_treatment_updated_event(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $this->service->updateTreatment($treatment->id, [
            'type' => 'Empaste mejorado',
        ]);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment.updated',
        ]);
    }

    public function test_update_requires_type_not_empty(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('type cannot be empty');

        $this->service->updateTreatment($treatment->id, [
            'type' => '',
        ]);
    }

    public function test_update_validates_amount_is_numeric(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('amount must be numeric');

        $this->service->updateTreatment($treatment->id, [
            'amount' => 'not-a-number',
        ]);
    }

    public function test_update_validates_amount_is_positive_or_zero(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('amount must be positive or zero');

        $this->service->updateTreatment($treatment->id, [
            'amount' => '-50',
        ]);
    }

    public function test_update_throws_exception_for_nonexistent_treatment(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment not found');

        $this->service->updateTreatment('non-existent-uuid', [
            'type' => 'Empaste',
        ]);
    }

    public function test_update_throws_exception_for_soft_deleted_treatment(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $treatment->delete(); // soft delete

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot update deleted treatment');

        $this->service->updateTreatment($treatment->id, [
            'type' => 'Endodoncia',
        ]);
    }

    // FASE 20.4: Tests for removeTreatmentFromVisit

    public function test_removes_treatment_successfully(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $this->service->removeTreatmentFromVisit($treatment->id);

        // Verify soft deleted in write model
        $this->assertSoftDeleted('visit_treatments', [
            'id' => $treatment->id,
        ]);
    }

    public function test_remove_emits_treatment_removed_event(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $this->service->removeTreatmentFromVisit($treatment->id);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment.removed',
        ]);
    }

    public function test_remove_throws_exception_for_nonexistent_treatment(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment not found');

        $this->service->removeTreatmentFromVisit('non-existent-uuid');
    }

    public function test_remove_throws_exception_for_already_deleted_treatment(): void
    {
        $treatment = $this->service->addTreatmentToVisit($this->visit->id, [
            'type' => 'Empaste',
        ]);

        $treatment->delete(); // soft delete

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment already deleted');

        $this->service->removeTreatmentFromVisit($treatment->id);
    }
}
