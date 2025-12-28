<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Clinic;
use App\Models\Professional;
use App\Models\ClinicalProfessional;
use App\Models\User;
use App\Services\OutboxEventConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfessionalCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Clinic $otherClinic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        $this->otherClinic = Clinic::factory()->create();

        // Set current clinic in app container
        app()->instance('currentClinicId', $this->clinic->id);
    }

    /** @test */
    public function it_shows_professional_catalog_index()
    {
        $response = $this->get(route('workspace.professionals.index'));

        $response->assertStatus(200);
        $response->assertSee('Gestiona el catálogo de profesionales de tu clínica');
    }

    /** @test */
    public function it_creates_a_professional_via_ui()
    {
        $response = $this->post(route('workspace.professionals.store'), [
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('professionals', [
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
            'active' => true,
        ]);

        // Process outbox events
        app(OutboxEventConsumer::class)->processPendingEvents();

        // Verify projection
        $this->assertDatabaseHas('clinical_professionals', [
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
            'active' => true,
        ]);
    }

    /** @test */
    public function it_updates_a_professional_via_ui()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Original',
            'role' => 'dentist',
        ]);

        $response = $this->patch(route('workspace.professionals.update', $professional->id), [
            'name' => 'Dr. Updated',
            'role' => 'hygienist',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'name' => 'Dr. Updated',
            'role' => 'hygienist',
        ]);
    }

    /** @test */
    public function it_deactivates_a_professional_via_ui()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'active' => true,
        ]);

        $response = $this->patch(route('workspace.professionals.deactivate', $professional->id));

        $response->assertStatus(200);

        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'active' => false,
        ]);
    }

    /** @test */
    public function it_activates_an_inactive_professional_via_ui()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'active' => false,
        ]);

        $response = $this->patch(route('workspace.professionals.activate', $professional->id));

        $response->assertStatus(200);

        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'active' => true,
        ]);
    }

    /** @test */
    public function professional_without_user_id_works()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Independent',
            'role' => 'dentist',
            'user_id' => null,
        ]);

        $this->assertNull($professional->user_id);
        $this->assertNotNull($professional->id);
        $this->assertEquals('Dr. Independent', $professional->name);
    }

    /** @test */
    public function professional_with_user_id_works()
    {
        $user = User::factory()->create();

        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Linked',
            'role' => 'dentist',
            'user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $professional->user_id);
        $this->assertNotNull($professional->user);
    }

    /** @test */
    public function inactive_professionals_do_not_appear_in_active_list()
    {
        $active = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Active',
            'active' => true,
        ]);

        $inactive = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Inactive',
            'active' => false,
        ]);

        // Process outbox
        app(OutboxEventConsumer::class)->processPendingEvents();

        $activeProfessionals = ClinicalProfessional::forClinic($this->clinic->id)
            ->active()
            ->get();

        $this->assertCount(1, $activeProfessionals);
        $this->assertEquals('Dr. Active', $activeProfessionals->first()->name);
    }

    /** @test */
    public function professionals_are_isolated_by_clinic()
    {
        $professional1 = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Clinic 1',
        ]);

        $professional2 = Professional::factory()->create([
            'clinic_id' => $this->otherClinic->id,
            'name' => 'Dr. Clinic 2',
        ]);

        // Process outbox
        app(OutboxEventConsumer::class)->processPendingEvents();

        $professionalsClinic1 = ClinicalProfessional::forClinic($this->clinic->id)->get();
        $professionalsClinic2 = ClinicalProfessional::forClinic($this->otherClinic->id)->get();

        $this->assertCount(1, $professionalsClinic1);
        $this->assertCount(1, $professionalsClinic2);
        $this->assertEquals('Dr. Clinic 1', $professionalsClinic1->first()->name);
        $this->assertEquals('Dr. Clinic 2', $professionalsClinic2->first()->name);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->post(route('workspace.professionals.store'), [
            // Missing name and role
        ]);

        $response->assertStatus(302); // Validation error redirect
        $this->assertDatabaseCount('professionals', 0);
    }

    /** @test */
    public function it_validates_role_must_be_valid_enum()
    {
        $response = $this->post(route('workspace.professionals.store'), [
            'name' => 'Dr. Test',
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(302); // Validation error
        $this->assertDatabaseCount('professionals', 0);
    }

    /** @test */
    public function projections_are_idempotent()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Idempotent',
        ]);

        // Process outbox twice
        app(OutboxEventConsumer::class)->processPendingEvents();
        app(OutboxEventConsumer::class)->processPendingEvents();

        // Should only have one projection
        $projections = ClinicalProfessional::where('clinic_id', $this->clinic->id)->get();
        $this->assertCount(1, $projections);
    }
}
