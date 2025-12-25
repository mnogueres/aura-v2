<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientSummary;
use App\Models\PatientTimeline;
use App\Models\BillingTimeline;
use App\Models\AuditTrail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        // Crear clínica de prueba
        $clinic = Clinic::create([
            'name' => 'Clínica Demo Aura',
        ]);

        // Configurar clinic_id actual
        app()->instance('currentClinicId', $clinic->id);

        $this->command->info("✓ Clínica creada: {$clinic->name} (ID: {$clinic->id})");

        // Crear 3 pacientes de ejemplo
        $patients = [
            [
                'dni' => '12345678A',
                'first_name' => 'Juan',
                'last_name' => 'Pérez García',
                'invoices' => 5,
                'payments' => 3,
                'total_invoiced' => 1500.00,
                'total_paid' => 750.00,
            ],
            [
                'dni' => '87654321B',
                'first_name' => 'María',
                'last_name' => 'González López',
                'invoices' => 8,
                'payments' => 6,
                'total_invoiced' => 2400.00,
                'total_paid' => 1800.00,
            ],
            [
                'dni' => '11223344C',
                'first_name' => 'Carlos',
                'last_name' => 'Martínez Ruiz',
                'invoices' => 3,
                'payments' => 2,
                'total_invoiced' => 900.00,
                'total_paid' => 450.00,
            ],
        ];

        foreach ($patients as $patientData) {
            $patient = Patient::create([
                'clinic_id' => $clinic->id,
                'dni' => $patientData['dni'],
                'first_name' => $patientData['first_name'],
                'last_name' => $patientData['last_name'],
                'email' => strtolower($patientData['first_name']) . '@example.com',
                'phone' => '600' . rand(100000, 999999),
            ]);

            // Crear PatientSummary
            PatientSummary::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'created_at_occurred' => now()->subDays(rand(30, 90)),
                'last_activity_at' => now()->subDays(rand(1, 7)),
                'invoices_count' => $patientData['invoices'],
                'payments_count' => $patientData['payments'],
                'total_invoiced_amount' => $patientData['total_invoiced'],
                'total_paid_amount' => $patientData['total_paid'],
                'projected_at' => now(),
            ]);

            // Crear eventos de PatientTimeline
            $timelineEvents = [
                ['event' => 'crm.patient.created', 'days_ago' => 90],
                ['event' => 'crm.patient.updated', 'days_ago' => 60],
                ['event' => 'crm.patient.updated', 'days_ago' => 30],
                ['event' => 'crm.patient.updated', 'days_ago' => 15],
                ['event' => 'crm.patient.updated', 'days_ago' => 5],
            ];

            foreach ($timelineEvents as $eventData) {
                PatientTimeline::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'event_name' => $eventData['event'],
                    'event_payload' => [
                        'patient_id' => $patient->id,
                        'changes' => ['updated_at' => now()->toISOString()],
                    ],
                    'occurred_at' => now()->subDays($eventData['days_ago']),
                    'projected_at' => now(),
                    'source_event_id' => (string) Str::uuid(),
                ]);
            }

            // Crear eventos de BillingTimeline
            $billingEvents = [
                ['event' => 'billing.invoice.created', 'amount' => 500.00, 'ref' => 1, 'days_ago' => 80],
                ['event' => 'billing.invoice.issued', 'amount' => 500.00, 'ref' => 1, 'days_ago' => 79],
                ['event' => 'billing.payment.recorded', 'amount' => 250.00, 'ref' => 1, 'days_ago' => 70],
                ['event' => 'billing.payment.applied', 'amount' => 250.00, 'ref' => 1, 'days_ago' => 70],
                ['event' => 'billing.invoice.created', 'amount' => 300.00, 'ref' => 2, 'days_ago' => 50],
                ['event' => 'billing.invoice.issued', 'amount' => 300.00, 'ref' => 2, 'days_ago' => 49],
                ['event' => 'billing.payment.recorded', 'amount' => 300.00, 'ref' => 2, 'days_ago' => 40],
                ['event' => 'billing.payment.applied', 'amount' => 300.00, 'ref' => 2, 'days_ago' => 40],
            ];

            foreach ($billingEvents as $eventData) {
                BillingTimeline::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'event_name' => $eventData['event'],
                    'amount' => $eventData['amount'],
                    'currency' => 'EUR',
                    'reference_id' => $eventData['ref'],
                    'event_payload' => [
                        'invoice_id' => $eventData['ref'],
                        'amount' => $eventData['amount'],
                    ],
                    'occurred_at' => now()->subDays($eventData['days_ago']),
                    'projected_at' => now(),
                    'source_event_id' => (string) Str::uuid(),
                ]);
            }

            $this->command->info("✓ Paciente creado: {$patient->first_name} {$patient->last_name} (ID: {$patient->id})");
        }

        // Crear algunos eventos de AuditTrail
        $auditEvents = [
            ['event' => 'platform.rate_limited', 'severity' => 'warning', 'category' => 'security', 'days_ago' => 10],
            ['event' => 'platform.idempotency.replayed', 'severity' => 'info', 'category' => 'platform', 'days_ago' => 8],
            ['event' => 'platform.rate_limited', 'severity' => 'warning', 'category' => 'security', 'days_ago' => 5],
            ['event' => 'platform.idempotency.conflict', 'severity' => 'error', 'category' => 'platform', 'days_ago' => 3],
        ];

        foreach ($auditEvents as $eventData) {
            AuditTrail::create([
                'clinic_id' => $clinic->id,
                'event_name' => $eventData['event'],
                'category' => $eventData['category'],
                'severity' => $eventData['severity'],
                'actor_type' => 'system',
                'actor_id' => null,
                'context' => [
                    'ip' => '127.0.0.1',
                    'user_agent' => 'Test',
                ],
                'occurred_at' => now()->subDays($eventData['days_ago']),
                'projected_at' => now(),
                'source_event_id' => (string) Str::uuid(),
            ]);
        }

        $this->command->info("✓ Audit trail creado (" . count($auditEvents) . " eventos)");

        $this->command->newLine();
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->info('✅ Workspace seeding completado');
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info("🏥 Clínica: {$clinic->name} (ID: {$clinic->id})");
        $this->command->info('👥 Pacientes creados: 3');
        $this->command->newLine();
        $this->command->info('🌐 Accede al workspace:');
        $this->command->info('   http://127.0.0.1:8000/workspace/patients/1');
        $this->command->info('   http://127.0.0.1:8000/workspace/patients/2');
        $this->command->info('   http://127.0.0.1:8000/workspace/patients/3');
        $this->command->newLine();
    }
}
