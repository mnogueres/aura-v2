<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientSummary;
use App\Models\BillingTimeline;
use App\Models\ClinicalVisit;
use App\Models\ClinicalTreatment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ValidationSeeder extends Seeder
{
    private $clinic;
    private $currentDate;

    public function run(): void
    {
        $this->currentDate = Carbon::now();

        // Obtener o crear clínica
        $this->clinic = Clinic::latest()->first();

        if (!$this->clinic) {
            $this->clinic = Clinic::create([
                'name' => 'Clínica Demo Aura',
            ]);
        }

        app()->instance('currentClinicId', $this->clinic->id);

        $this->command->info("════════════════════════════════════════════════════");
        $this->command->info("  FASE 18 - VALIDATION SEEDER");
        $this->command->info("════════════════════════════════════════════════════");
        $this->command->newLine();

        // Paciente A: Alta carga (15-20 visitas)
        $this->createHighLoadPatient();

        // Paciente B: Baja carga (2 visitas, 1 tratamiento)
        $this->createLowLoadPatient();

        // Paciente C: Solo administrativo (sin visitas)
        $this->createAdminOnlyPatient();

        $this->command->newLine();
        $this->command->info("════════════════════════════════════════════════════");
        $this->command->info("✅ VALIDATION SEEDING COMPLETADO");
        $this->command->info("════════════════════════════════════════════════════");
        $this->command->newLine();
        $this->command->info("🌐 Accede al workspace:");

        $patients = Patient::latest()->take(3)->get()->reverse();
        foreach ($patients as $patient) {
            $this->command->info("   http://127.0.0.1:8000/workspace/patients/{$patient->id}");
        }
        $this->command->newLine();
    }

    private function createHighLoadPatient(): void
    {
        $this->command->info("📊 PACIENTE A: Alta carga (15-20 visitas)");

        $patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'dni' => '45678901A',
            'first_name' => 'Ana',
            'last_name' => 'Rodríguez Sánchez',
            'email' => 'ana.rodriguez@example.com',
            'phone' => '654321789',
            'birth_date' => '1985-03-15',
        ]);

        $this->command->info("   ✓ Paciente creado: {$patient->first_name} {$patient->last_name} (ID: {$patient->id})");

        // Crear 18 visitas variadas a lo largo de 2 años
        $visitScenarios = [
            // Hace 2 años - inicio del tratamiento
            ['days_ago' => 720, 'professional' => 'Dr. García', 'type' => 'primera_visita', 'summary' => 'Primera visita. Revisión completa', 'treatments' => [
                ['type' => 'Radiografía panorámica', 'tooth' => null, 'amount' => 45.00],
                ['type' => 'Limpieza bucal', 'tooth' => null, 'amount' => 60.00],
            ]],

            // Hace 22 meses - tratamiento complejo
            ['days_ago' => 660, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Inicio de tratamiento endodoncia', 'treatments' => [
                ['type' => 'Endodoncia', 'tooth' => '16', 'amount' => 280.00, 'notes' => 'Primera sesión'],
            ]],

            ['days_ago' => 650, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Continuación endodoncia pieza 16', 'treatments' => [
                ['type' => 'Endodoncia', 'tooth' => '16', 'amount' => 280.00, 'notes' => 'Segunda sesión'],
            ]],

            ['days_ago' => 640, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Finalización endodoncia y corona', 'treatments' => [
                ['type' => 'Endodoncia', 'tooth' => '16', 'amount' => 280.00, 'notes' => 'Sesión final'],
                ['type' => 'Corona cerámica', 'tooth' => '16', 'amount' => 450.00],
            ]],

            // Hace 18 meses - mantenimiento
            ['days_ago' => 540, 'professional' => 'Dra. Pérez', 'type' => 'revision', 'summary' => 'Revisión semestral', 'treatments' => []],

            // Hace 15 meses - empastes
            ['days_ago' => 450, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Detección de caries en revisión', 'treatments' => [
                ['type' => 'Empaste composite', 'tooth' => '36', 'amount' => 65.00],
                ['type' => 'Empaste composite', 'tooth' => '37', 'amount' => 65.00],
            ]],

            // Hace 12 meses - limpieza anual
            ['days_ago' => 365, 'professional' => 'Dra. Pérez', 'type' => 'prevencion', 'summary' => 'Limpieza anual + revisión', 'treatments' => [
                ['type' => 'Limpieza bucal', 'tooth' => null, 'amount' => 60.00],
            ]],

            // Hace 10 meses - urgencia
            ['days_ago' => 300, 'professional' => 'Dr. García', 'type' => 'urgencia', 'summary' => 'Dolor agudo molar inferior izquierdo', 'treatments' => [
                ['type' => 'Radiografía periapical', 'tooth' => '46', 'amount' => 25.00],
                ['type' => 'Tratamiento dolor', 'tooth' => '46', 'amount' => 40.00],
            ]],

            ['days_ago' => 295, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Endodoncia pieza 46', 'treatments' => [
                ['type' => 'Endodoncia', 'tooth' => '46', 'amount' => 280.00],
            ]],

            ['days_ago' => 285, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Corona sobre pieza 46', 'treatments' => [
                ['type' => 'Corona metal-cerámica', 'tooth' => '46', 'amount' => 380.00],
            ]],

            // Hace 6 meses - revisión
            ['days_ago' => 180, 'professional' => 'Dra. Pérez', 'type' => 'revision', 'summary' => 'Revisión semestral de control', 'treatments' => []],

            // Hace 4 meses - empaste pequeño
            ['days_ago' => 120, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Caries incipiente detectada', 'treatments' => [
                ['type' => 'Empaste composite', 'tooth' => '24', 'amount' => 65.00],
            ]],

            // Hace 3 meses - estética
            ['days_ago' => 90, 'professional' => 'Dra. Pérez', 'type' => 'estetica', 'summary' => 'Solicita blanqueamiento dental', 'treatments' => [
                ['type' => 'Blanqueamiento LED', 'tooth' => null, 'amount' => 250.00],
            ]],

            // Hace 2 meses - seguimiento blanqueamiento
            ['days_ago' => 60, 'professional' => 'Dra. Pérez', 'type' => 'revision', 'summary' => 'Control post-blanqueamiento', 'treatments' => []],

            // Hace 1 mes - limpieza
            ['days_ago' => 30, 'professional' => 'Dra. Pérez', 'type' => 'prevencion', 'summary' => 'Limpieza y fluoración', 'treatments' => [
                ['type' => 'Limpieza bucal', 'tooth' => null, 'amount' => 60.00],
                ['type' => 'Aplicación flúor', 'tooth' => null, 'amount' => 25.00],
            ]],

            // Hace 2 semanas - empaste
            ['days_ago' => 14, 'professional' => 'Dr. García', 'type' => 'tratamiento', 'summary' => 'Empaste preventivo', 'treatments' => [
                ['type' => 'Empaste composite', 'tooth' => '15', 'amount' => 65.00],
            ]],

            // Hace 3 días - revisión reciente
            ['days_ago' => 3, 'professional' => 'Dra. Pérez', 'type' => 'revision', 'summary' => 'Control trimestral. Todo correcto', 'treatments' => []],

            // Ayer - valoración ortodoncia
            ['days_ago' => 1, 'professional' => 'Dr. Martínez', 'type' => 'valoracion', 'summary' => 'Valoración para ortodoncia invisible', 'treatments' => [
                ['type' => 'Estudio ortodóncico', 'tooth' => null, 'amount' => 120.00],
            ]],
        ];

        $totalInvoiced = 0;
        $totalPaid = 0;
        $invoicesCount = 0;
        $paymentsCount = 0;

        foreach ($visitScenarios as $index => $scenario) {
            $visitDate = $this->currentDate->copy()->subDays($scenario['days_ago']);

            $visit = ClinicalVisit::create([
                'id' => (string) Str::uuid(),
                'clinic_id' => $this->clinic->id,
                'patient_id' => $patient->id,
                'occurred_at' => $visitDate,
                'professional_name' => $scenario['professional'],
                'visit_type' => $scenario['type'],
                'summary' => $scenario['summary'],
                'treatments_count' => count($scenario['treatments']),
                'projected_at' => $this->currentDate,
                'source_event_id' => (string) Str::uuid(),
            ]);

            $visitTotal = 0;

            foreach ($scenario['treatments'] as $treatment) {
                ClinicalTreatment::create([
                    'id' => (string) Str::uuid(),
                    'clinic_id' => $this->clinic->id,
                    'patient_id' => $patient->id,
                    'visit_id' => $visit->id,
                    'type' => $treatment['type'],
                    'tooth' => $treatment['tooth'] ?? null,
                    'amount' => $treatment['amount'],
                    'notes' => $treatment['notes'] ?? null,
                    'projected_at' => $this->currentDate,
                    'source_event_id' => (string) Str::uuid(),
                ]);

                $visitTotal += $treatment['amount'];
            }

            // Crear facturación si hay tratamientos
            if ($visitTotal > 0) {
                $invoiceRef = "INV-A-" . str_pad($invoicesCount + 1, 3, '0', STR_PAD_LEFT);
                $invoicesCount++;
                $totalInvoiced += $visitTotal;

                // Factura creada
                BillingTimeline::create([
                    'clinic_id' => $this->clinic->id,
                    'patient_id' => $patient->id,
                    'event_name' => 'billing.invoice.created',
                    'amount' => $visitTotal,
                    'currency' => 'EUR',
                    'reference_id' => $invoiceRef,
                    'event_payload' => ['invoice_id' => $invoiceRef],
                    'occurred_at' => $visitDate->copy()->addHours(2),
                    'projected_at' => $this->currentDate,
                    'source_event_id' => (string) Str::uuid(),
                ]);

                // Algunas facturas pagadas totalmente, otras parcialmente, algunas pendientes
                if ($scenario['days_ago'] > 60) {
                    // Facturas antiguas: pagadas totalmente
                    $totalPaid += $visitTotal;
                    $paymentsCount++;

                    BillingTimeline::create([
                        'clinic_id' => $this->clinic->id,
                        'patient_id' => $patient->id,
                        'event_name' => 'billing.payment.recorded',
                        'amount' => $visitTotal,
                        'currency' => 'EUR',
                        'reference_id' => $invoiceRef,
                        'event_payload' => ['invoice_id' => $invoiceRef],
                        'occurred_at' => $visitDate->copy()->addDays(7),
                        'projected_at' => $this->currentDate,
                        'source_event_id' => (string) Str::uuid(),
                    ]);
                } elseif ($scenario['days_ago'] > 30) {
                    // Facturas intermedias: pagadas 50%
                    $partialAmount = $visitTotal * 0.5;
                    $totalPaid += $partialAmount;
                    $paymentsCount++;

                    BillingTimeline::create([
                        'clinic_id' => $this->clinic->id,
                        'patient_id' => $patient->id,
                        'event_name' => 'billing.payment.recorded',
                        'amount' => $partialAmount,
                        'currency' => 'EUR',
                        'reference_id' => $invoiceRef,
                        'event_payload' => ['invoice_id' => $invoiceRef, 'partial' => true],
                        'occurred_at' => $visitDate->copy()->addDays(5),
                        'projected_at' => $this->currentDate,
                        'source_event_id' => (string) Str::uuid(),
                    ]);
                }
                // Facturas recientes (<30 días): sin pagar
            }
        }

        // Crear/actualizar PatientSummary
        PatientSummary::updateOrCreate(
            [
                'clinic_id' => $this->clinic->id,
                'patient_id' => $patient->id,
            ],
            [
                'created_at_occurred' => $this->currentDate->copy()->subDays(720),
                'last_activity_at' => $this->currentDate->copy()->subDays(1),
                'invoices_count' => $invoicesCount,
                'payments_count' => $paymentsCount,
                'total_invoiced_amount' => $totalInvoiced,
                'total_paid_amount' => $totalPaid,
                'projected_at' => $this->currentDate,
            ]
        );

        $this->command->info("   ✓ " . count($visitScenarios) . " visitas clínicas creadas");
        $this->command->info("   ✓ {$invoicesCount} facturas (Total: €{$totalInvoiced})");
        $this->command->info("   ✓ {$paymentsCount} pagos (Total: €{$totalPaid})");
        $this->command->newLine();
    }

    private function createLowLoadPatient(): void
    {
        $this->command->info("📊 PACIENTE B: Baja carga (2 visitas, 1 tratamiento)");

        $patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'dni' => '56789012B',
            'first_name' => 'Luis',
            'last_name' => 'Fernández Mora',
            'email' => 'luis.fernandez@example.com',
            'phone' => '678901234',
            'birth_date' => '1992-07-22',
        ]);

        $this->command->info("   ✓ Paciente creado: {$patient->first_name} {$patient->last_name} (ID: {$patient->id})");

        // Visita 1: Revisión hace 6 meses (sin tratamiento)
        $visit1Date = $this->currentDate->copy()->subDays(180);

        $visit1 = ClinicalVisit::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'occurred_at' => $visit1Date,
            'professional_name' => 'Dra. Pérez',
            'visit_type' => 'revision',
            'summary' => 'Revisión anual. Estado dental correcto',
            'treatments_count' => 0,
            'projected_at' => $this->currentDate,
            'source_event_id' => (string) Str::uuid(),
        ]);

        // Visita 2: Limpieza hace 1 mes (con tratamiento)
        $visit2Date = $this->currentDate->copy()->subDays(30);

        $visit2 = ClinicalVisit::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'occurred_at' => $visit2Date,
            'professional_name' => 'Dra. Pérez',
            'visit_type' => 'prevencion',
            'summary' => 'Limpieza dental profesional',
            'treatments_count' => 1,
            'projected_at' => $this->currentDate,
            'source_event_id' => (string) Str::uuid(),
        ]);

        ClinicalTreatment::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit2->id,
            'type' => 'Limpieza bucal',
            'tooth' => null,
            'amount' => 60.00,
            'notes' => null,
            'projected_at' => $this->currentDate,
            'source_event_id' => (string) Str::uuid(),
        ]);

        // Facturación de la limpieza
        $invoiceRef = "INV-B-001";

        BillingTimeline::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'billing.invoice.created',
            'amount' => 60.00,
            'currency' => 'EUR',
            'reference_id' => $invoiceRef,
            'event_payload' => ['invoice_id' => $invoiceRef],
            'occurred_at' => $visit2Date->copy()->addHours(1),
            'projected_at' => $this->currentDate,
            'source_event_id' => (string) Str::uuid(),
        ]);

        BillingTimeline::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'billing.payment.recorded',
            'amount' => 60.00,
            'currency' => 'EUR',
            'reference_id' => $invoiceRef,
            'event_payload' => ['invoice_id' => $invoiceRef],
            'occurred_at' => $visit2Date->copy()->addDays(1),
            'projected_at' => $this->currentDate,
            'source_event_id' => (string) Str::uuid(),
        ]);

        // PatientSummary
        PatientSummary::updateOrCreate(
            [
                'clinic_id' => $this->clinic->id,
                'patient_id' => $patient->id,
            ],
            [
                'created_at_occurred' => $this->currentDate->copy()->subDays(200),
                'last_activity_at' => $visit2Date,
                'invoices_count' => 1,
                'payments_count' => 1,
                'total_invoiced_amount' => 60.00,
                'total_paid_amount' => 60.00,
                'projected_at' => $this->currentDate,
            ]
        );

        $this->command->info("   ✓ 2 visitas clínicas creadas");
        $this->command->info("   ✓ 1 factura pagada (Total: €60.00)");
        $this->command->newLine();
    }

    private function createAdminOnlyPatient(): void
    {
        $this->command->info("📊 PACIENTE C: Solo administrativo (sin visitas)");

        $patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'dni' => '67890123C',
            'first_name' => 'Carmen',
            'last_name' => 'López Jiménez',
            'email' => 'carmen.lopez@example.com',
            'phone' => '689012345',
            'birth_date' => '1978-11-30',
            'notes' => 'Paciente registrado pendiente de primera cita',
        ]);

        $this->command->info("   ✓ Paciente creado: {$patient->first_name} {$patient->last_name} (ID: {$patient->id})");

        // PatientSummary vacío
        PatientSummary::updateOrCreate(
            [
                'clinic_id' => $this->clinic->id,
                'patient_id' => $patient->id,
            ],
            [
                'created_at_occurred' => $this->currentDate->copy()->subDays(7),
                'last_activity_at' => $this->currentDate->copy()->subDays(7),
                'invoices_count' => 0,
                'payments_count' => 0,
                'total_invoiced_amount' => 0,
                'total_paid_amount' => 0,
                'projected_at' => $this->currentDate,
            ]
        );

        $this->command->info("   ✓ Sin visitas clínicas");
        $this->command->info("   ✓ Sin facturación");
        $this->command->newLine();
    }
}
