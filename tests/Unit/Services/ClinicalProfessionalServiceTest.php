<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ClinicalProfessionalService;
use App\Services\EventService;
use App\Models\Professional;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClinicalProfessionalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClinicalProfessionalService $service;
    private Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ClinicalProfessionalService::class);
        $this->clinic = Clinic::factory()->create();
    }

    /** @test */
    public function it_creates_a_professional()
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
        ];

        $professional = $this->service->createProfessional($data);

        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
            'active' => true,
        ]);
    }

    /** @test */
    public function it_creates_professional_with_user_id()
    {
        $user = \App\Models\User::factory()->create();

        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
            'user_id' => $user->id,
        ];

        $professional = $this->service->createProfessional($data);

        $this->assertEquals($user->id, $professional->user_id);
    }

    /** @test */
    public function it_creates_professional_without_user_id()
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. María López',
            'role' => 'hygienist',
        ];

        $professional = $this->service->createProfessional($data);

        $this->assertNull($professional->user_id);
    }

    /** @test */
    public function it_updates_a_professional()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'dentist',
        ]);

        $updated = $this->service->updateProfessional($professional->id, [
            'name' => 'Dr. Juan García',
            'role' => 'hygienist',
        ]);

        $this->assertEquals('Dr. Juan García', $updated->name);
        $this->assertEquals('hygienist', $updated->role);
    }

    /** @test */
    public function it_deactivates_a_professional()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'active' => true,
        ]);

        $deactivated = $this->service->deactivateProfessional($professional->id);

        $this->assertFalse($deactivated->active);
        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'active' => false,
        ]);
    }

    /** @test */
    public function it_throws_exception_when_name_is_missing()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Professional name is required');

        $this->service->createProfessional([
            'clinic_id' => $this->clinic->id,
            'role' => 'dentist',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_role_is_missing()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Professional role is required');

        $this->service->createProfessional([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_role_is_invalid()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid role');

        $this->service->createProfessional([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Juan Pérez',
            'role' => 'invalid_role',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_deactivating_already_inactive_professional()
    {
        $professional = Professional::factory()->create([
            'clinic_id' => $this->clinic->id,
            'active' => false,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Professional is already inactive');

        $this->service->deactivateProfessional($professional->id);
    }

    /** @test */
    public function it_emits_professional_created_event()
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Test',
            'role' => 'dentist',
        ];

        $professional = $this->service->createProfessional($data);

        $this->assertDatabaseHas('outbox_events', [
            'event_name' => 'clinical.professional.created',
            'clinic_id' => $this->clinic->id,
        ]);
    }
}
