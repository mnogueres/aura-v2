<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiRequestLoggerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_and_propagates_request_id_in_response_header()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/patients');

        $response->assertHeader('X-Request-Id');

        $requestId = $response->headers->get('X-Request-Id');

        $this->assertTrue(Str::isUuid($requestId));
    }

    /** @test */
    public function it_logs_request_to_api_channel_with_required_fields()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);

        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'API Request'
                    && isset($context['request_id'])
                    && Str::isUuid($context['request_id'])
                    && $context['method'] === 'GET'
                    && isset($context['url'])
                    && isset($context['path'])
                    && isset($context['ip'])
                    && isset($context['status'])
                    && isset($context['duration_ms']);
            });

        $response = $this->getJson('/api/v1/patients');
    }

    /** @test */
    public function it_does_not_log_sensitive_headers()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);

        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                // Verify that Authorization and Cookie headers are NOT in the context
                return !isset($context['Authorization'])
                    && !isset($context['Cookie'])
                    && !isset($context['headers']);
            });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Cookie' => 'session=secret',
        ])->getJson('/api/v1/patients');
    }

    /** @test */
    public function it_does_not_log_request_body()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);

        app()->instance('currentClinicId', $clinic->id);

        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                // Verify that request body is NOT in the context
                return !isset($context['body'])
                    && !isset($context['first_name'])
                    && !isset($context['email'])
                    && !isset($context['request_data']);
            });

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/patients', [
                'clinic_id' => $clinic->id,
                'first_name' => 'Ana',
                'last_name' => 'Test',
                'email' => 'sensitive@example.com',
            ]);
    }

    /** @test */
    public function it_logs_correct_status_code()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);

        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $context['status'] === 200;
            });

        $response = $this->getJson('/api/v1/patients');
    }

    /** @test */
    public function it_logs_request_duration_in_milliseconds()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);

        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['duration_ms'])
                    && is_numeric($context['duration_ms'])
                    && $context['duration_ms'] >= 0;
            });

        $response = $this->getJson('/api/v1/patients');
    }
}
