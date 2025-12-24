<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_patient()
    {
        $clinic = Clinic::create([
            'name' => 'Test Clinic',
        ]);

        $user = User::factory()->create([
            'clinic_id' => $clinic->id,
        ]);

        $this->actingAs($user);

        app()->instance('currentClinicId', $clinic->id);

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/patients', [
                'clinic_id'  => $clinic->id,
                'first_name' => 'Ana',
                'last_name'  => 'Test',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.first_name', 'Ana')
            ->assertJsonPath('data.last_name', 'Test');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Ana',
            'clinic_id'  => $clinic->id,
        ]);
    }

    /** @test */
    public function it_lists_only_patients_of_the_current_clinic()
    {
        $clinicA = Clinic::create(['name' => 'Clinic A']);
        $clinicB = Clinic::create(['name' => 'Clinic B']);

        Patient::create([
            'clinic_id'  => $clinicA->id,
            'first_name' => 'Alice',
            'last_name'  => 'A',
        ]);

        Patient::create([
            'clinic_id'  => $clinicB->id,
            'first_name' => 'Bob',
            'last_name'  => 'B',
        ]);

        app()->instance('currentClinicId', $clinicA->id);

        $response = $this->getJson('/api/v1/patients');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Alice');
    }

    /** @test */
    public function it_returns_api_envelope_with_pagination_meta()
    {
        $clinic = Clinic::create(['name' => 'Clinic A']);

        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson('/api/v1/patients');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                    ],
                ],
            ])
            ->assertJsonPath('meta.pagination.per_page', 8);
    }

    /** @test */
    public function it_returns_standard_error_on_validation_failure()
    {
        $clinic = Clinic::create(['name' => 'Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/patients', []);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'data',
                'error' => ['code', 'message', 'details'],
                'meta' => ['request_id'],
            ])
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('data', null);
    }
}
