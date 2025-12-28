<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Visit;
use App\Models\ClinicalProfessional;
use App\Models\ClinicalVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * VisitProfessionalIntegrationTest - FASE 21.1
 *
 * Tests integration between visits and professionals catalog.
 * Verifies that professionals can be assigned to visits correctly.
 */
class VisitProfessionalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;
    private Professional $activeProfessional;
    private Professional $inactiveProfessional;

    protected function setUp(): void
    {
        parent::setUp();

        // Create clinic
        $this->clinic = Clinic::factory()->create();

        // Create patient
        $this->patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'dni' => '12345678A',
            'email' => 'test@example.com',
        ]);

        // Create active professional
        $this->activeProfessional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Active',
            'role' => 'dentist',
            'active' => true,
        ]);

        // Create inactive professional
        $this->inactiveProfessional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Inactive',
            'role' => 'hygienist',
            'active' => false,
        ]);
    }

    /** @test */
    public function visit_can_be_created_with_active_professional()
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->activeProfessional->id,
            'occurred_at' => now(),
            'visit_type' => 'Revisión',
            'summary' => 'Test visit',
        ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'professional_id' => $this->activeProfessional->id,
        ]);

        $this->assertEquals($this->activeProfessional->id, $visit->professional_id);
    }

    /** @test */
    public function visit_can_be_created_without_professional()
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => null,
            'occurred_at' => now(),
            'visit_type' => 'Revisión',
            'summary' => 'Test visit',
        ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'professional_id' => null,
        ]);

        $this->assertNull($visit->professional_id);
    }

    /** @test */
    public function visit_professional_can_be_updated()
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->activeProfessional->id,
            'occurred_at' => now(),
        ]);

        // Create another professional
        $newProfessional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. New',
            'role' => 'dentist',
            'active' => true,
        ]);

        // Update visit to new professional
        $visit->update(['professional_id' => $newProfessional->id]);

        $this->assertEquals($newProfessional->id, $visit->fresh()->professional_id);
    }

    /** @test */
    public function visit_can_reference_inactive_professional()
    {
        // This tests the scenario where a professional is deactivated
        // but existing visits should still reference them
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->inactiveProfessional->id,
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'professional_id' => $this->inactiveProfessional->id,
        ]);

        // Professional relationship should still work
        $this->assertEquals('Dr. Inactive', $visit->professional->name);
        $this->assertFalse($visit->professional->active);
    }

    /** @test */
    public function only_active_professionals_are_shown_in_active_scope()
    {
        // Create professional projections
        ClinicalProfessional::create([
            'id' => $this->activeProfessional->id,
            'clinic_id' => $this->clinic->id,
            'name' => $this->activeProfessional->name,
            'role' => $this->activeProfessional->role,
            'active' => true,
            'projected_at' => now(),
        ]);

        ClinicalProfessional::create([
            'id' => $this->inactiveProfessional->id,
            'clinic_id' => $this->clinic->id,
            'name' => $this->inactiveProfessional->name,
            'role' => $this->inactiveProfessional->role,
            'active' => false,
            'projected_at' => now(),
        ]);

        $activeProfessionals = ClinicalProfessional::forClinic($this->clinic->id)
            ->active()
            ->get();

        $this->assertCount(1, $activeProfessionals);
        $this->assertEquals('Dr. Active', $activeProfessionals->first()->name);
    }

    /** @test */
    public function professional_soft_delete_preserves_visit_reference()
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->activeProfessional->id,
            'occurred_at' => now(),
        ]);

        // Soft delete the professional
        $this->activeProfessional->delete();

        // Visit should still exist with professional_id intact
        // (soft delete doesn't trigger FK constraint)
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'professional_id' => $this->activeProfessional->id,
        ]);

        // Refresh visit - professional_id should still be there
        $visit->refresh();
        $this->assertEquals($this->activeProfessional->id, $visit->professional_id);

        // Professional relationship should still work (withTrashed)
        $this->assertNotNull($visit->professional()->withTrashed()->first());
    }
}
