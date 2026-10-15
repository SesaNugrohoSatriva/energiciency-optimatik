<?php

namespace Tests\Feature;

use App\Models\EnergyRecord;
use App\Models\Machine;
use App\Models\OeeRecord;
use App\Models\ProductionBatch;
use App\Models\ProductionOrder;
use App\Models\Product;
use App\Models\User;
use App\Services\EnergyAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MachineEnergyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_energy_totals_and_average_record_value_respect_the_selected_time_range(): void
    {
        $machine = $this->machine();
        $this->energy($machine, 'EVT-RANGE-1', '2025-01-01 08:00:00', 10, 20);
        $this->energy($machine, 'EVT-RANGE-2', '2025-01-02 08:00:00', 5, 12);
        $this->energy($machine, 'EVT-RANGE-3', '2025-01-03 08:00:00', 40, 80);

        $filters = ['from' => '2025-01-01', 'to' => '2025-01-02'];
        $summary = app(EnergyAnalyticsService::class)->summary(
            app(EnergyAnalyticsService::class)->query($filters, $machine->id)
        );

        $this->assertSame(2, $summary['record_count']);
        $this->assertSame(1, $summary['machine_count']);
        $this->assertEquals(15, $summary['total_kwh']);
        $this->assertEquals(32, $summary['total_cost']);
        $this->assertEquals(7.5, $summary['average_record_kwh']);
    }

    public function test_hour_day_week_and_month_buckets_aggregate_stored_values_without_inventing_gaps(): void
    {
        $machine = $this->machine();
        $this->energy($machine, 'EVT-BUCKET-1', '2025-01-01 08:00:00', 10, 20);
        $this->energy($machine, 'EVT-BUCKET-2', '2025-01-01 14:00:00', 5, 10);
        $this->energy($machine, 'EVT-BUCKET-3', '2025-01-08 08:00:00', 8, 16);
        $this->energy($machine, 'EVT-BUCKET-4', '2025-02-02 08:00:00', 4, 8);
        $service = app(EnergyAnalyticsService::class);
        $query = $service->query(['from' => '2025-01-01', 'to' => '2025-02-28'], $machine->id);

        $hourly = $service->trend(clone $query, 'hour');
        $daily = $service->trend(clone $query, 'day');
        $weekly = $service->trend(clone $query, 'week');
        $monthly = $service->trend(clone $query, 'month');

        $this->assertCount(4, $hourly);
        $this->assertCount(3, $daily);
        $this->assertCount(3, $weekly);
        $this->assertCount(2, $monthly);
        $this->assertEquals(15, $daily[0]['total_kwh']);
        $this->assertSame('2025-01', $monthly[0]['bucket']);
        $this->assertSame('2025-02', $monthly[1]['bucket']);
    }

    public function test_previous_period_summary_uses_the_equal_length_period_before_the_selected_range(): void
    {
        $machine = $this->machine();
        $this->energy($machine, 'EVT-PREVIOUS', '2025-01-01 12:00:00', 6, 12);
        $this->energy($machine, 'EVT-CURRENT', '2025-01-08 12:00:00', 10, 20);

        $previous = app(EnergyAnalyticsService::class)->previousPeriodSummary([
            'from' => '2025-01-08',
            'to' => '2025-01-14',
        ], $machine->id);

        $this->assertNotNull($previous);
        $this->assertEquals(6, $previous['total_kwh']);
        $this->assertSame(1, $previous['record_count']);
    }

    public function test_energy_monitoring_filters_machine_source_and_dates(): void
    {
        $machine = $this->machine();
        $this->energy($machine, 'EVT-FILTER-1', '2025-01-02 12:00:00', 7, 14, 'simulation');
        $this->energy($machine, 'EVT-FILTER-2', '2025-01-02 13:00:00', 11, 22, 'iot');

        $response = $this->actingAs($this->user())->get(route('energy-records.index', [
            'from' => '2025-01-01',
            'to' => '2025-01-03',
            'machine_id' => $machine->id,
            'source' => 'simulation',
            'search' => 'Mesin Uji Analitik',
            'interval' => 'day',
        ]));

        $response->assertOk()
            ->assertSee('EVT-FILTER-1')
            ->assertDontSee('EVT-FILTER-2')
            ->assertSee('7,00 kWh')
            ->assertSee('SIMULASI');
    }

    public function test_machine_detail_uses_oee_inputs_and_never_claims_device_online_from_a_past_reading(): void
    {
        $machine = $this->machine();
        $this->energy($machine, 'EVT-MACHINE', '2025-01-02 12:00:00', 12, 24, 'simulation');
        OeeRecord::create([
            'machine_id' => $machine->id,
            'recorded_on' => '2025-01-02',
            'planned_minutes' => 480,
            'operating_minutes' => 400,
            'ideal_cycle_minutes' => 0.5,
            'total_output' => 700,
            'good_output' => 672,
            'availability' => 400 / 480,
            'performance' => 350 / 400,
            'quality' => 672 / 700,
            'oee' => (400 / 480) * (350 / 400) * (672 / 700),
        ]);

        $response = $this->actingAs($this->user())->get(route('machines.show', [
            'record' => $machine->id,
            'from' => '2025-01-01',
            'to' => '2025-01-03',
        ]));

        $response->assertOk()
            ->assertSee('Status koneksi IoT')
            ->assertSee('backend tidak menyimpan heartbeat')
            ->assertSee('70,0 %')
            ->assertSee('EVT-MACHINE')
            ->assertSee('SIMULASI');
    }

    public function test_energy_record_detail_redacts_sensitive_payload_keys(): void
    {
        $machine = $this->machine();
        $record = $this->energy($machine, 'EVT-PAYLOAD', '2025-01-02 12:00:00', 3, 6);
        $record->update(['payload' => [
            'reading' => ['kwh' => 3],
            'api_token' => 'do-not-display-this',
            'nested' => ['password' => 'also-secret'],
        ]]);

        $response = $this->actingAs($this->user())->get(route('energy-records.show', $record->id));

        $response->assertOk()
            ->assertSee('[REDACTED]')
            ->assertDontSee('do-not-display-this', false);
    }

    public function test_machine_detail_shows_unavailable_metrics_instead_of_fabricating_values(): void
    {
        $machine = $this->machine();

        $response = $this->actingAs($this->user())->get(route('machines.show', [
            'record' => $machine->id,
            'from' => '2025-01-01',
            'to' => '2025-01-03',
        ]));

        $response->assertOk()
            ->assertSee('Data belum tersedia')
            ->assertSee('Tidak tersedia — backend tidak menyimpan heartbeat')
            ->assertDontSee('Online');
    }

    public function test_machine_detail_aggregates_only_energy_explicitly_linked_to_order_and_batch(): void
    {
        $machine = $this->machine();
        $product = Product::create(['sku' => 'ENERGY-UNIT-001', 'name' => 'Produk Uji Energi']);
        $order = ProductionOrder::create([
            'order_number' => 'ENERGY-ORDER-001',
            'product_id' => $product->id,
            'status' => 'Completed',
            'planned_quantity' => 6,
            'actual_quantity' => 5,
            'good_quantity' => 4,
        ]);
        $batch = ProductionBatch::create([
            'production_order_id' => $order->id,
            'batch_number' => 'ENERGY-BATCH-001',
            'planned_quantity' => 6,
            'actual_quantity' => 5,
            'good_quantity' => 4,
            'status' => 'completed',
        ]);
        $record = $this->energy($machine, 'EVT-ALLOCATED', '2025-01-02 12:00:00', 6, 24);
        $record->update([
            'production_order_id' => $order->id,
            'production_batch_id' => $batch->id,
        ]);

        $response = $this->actingAs($this->user())->get(route('machines.show', [
            'record' => $machine->id,
            'from' => '2025-01-01',
            'to' => '2025-01-03',
        ]));

        $response->assertOk()
            ->assertSee('ENERGY-ORDER-001')
            ->assertSee('ENERGY-BATCH-001')
            ->assertSee('1,500 kWh/unit baik')
            ->assertSee('Rp 6,00');
    }

    public function test_iot_ingest_keeps_external_event_id_idempotent_on_retries(): void
    {
        $machine = $this->machine();
        $previousToken = getenv('IOT_INGEST_TOKEN');
        $previousEnvToken = $_ENV['IOT_INGEST_TOKEN'] ?? null;
        $previousServerToken = $_SERVER['IOT_INGEST_TOKEN'] ?? null;
        putenv('IOT_INGEST_TOKEN=analytics-test-token');
        $_ENV['IOT_INGEST_TOKEN'] = 'analytics-test-token';
        $_SERVER['IOT_INGEST_TOKEN'] = 'analytics-test-token';
        $this->beforeApplicationDestroyed(function () use ($previousToken, $previousEnvToken, $previousServerToken): void {
            putenv($previousToken === false ? 'IOT_INGEST_TOKEN' : 'IOT_INGEST_TOKEN='.$previousToken);
            if ($previousEnvToken === null) {
                unset($_ENV['IOT_INGEST_TOKEN']);
            } else {
                $_ENV['IOT_INGEST_TOKEN'] = $previousEnvToken;
            }
            if ($previousServerToken === null) {
                unset($_SERVER['IOT_INGEST_TOKEN']);
            } else {
                $_SERVER['IOT_INGEST_TOKEN'] = $previousServerToken;
            }
        });
        $payload = [
            'device_id' => 'DEVICE-TEST-1',
            'external_event_id' => 'IOT-RETRY-001',
            'recorded_at' => '2025-01-02T05:00:00Z',
            'machine_code' => $machine->code,
            'kwh' => 4.25,
            'payload' => ['reading' => 4.25],
        ];

        $first = $this->withHeader('X-IOT-TOKEN', 'analytics-test-token')->postJson('/api/iot/telemetry', $payload);
        $first->assertCreated()->assertJsonPath('duplicate', false);

        $retry = $this->withHeader('X-IOT-TOKEN', 'analytics-test-token')->postJson('/api/iot/telemetry', [
            ...$payload,
            'kwh' => 99,
            'payload' => ['reading' => 99],
        ]);
        $retry->assertOk()->assertJsonPath('duplicate', true);

        $this->assertSame(1, EnergyRecord::where('external_event_id', 'IOT-RETRY-001')->count());
        $record = EnergyRecord::where('external_event_id', 'IOT-RETRY-001')->firstOrFail();
        $this->assertEquals(4.25, (float) $record->kwh);
        $this->assertSame('2025-01-02 12:00:00', $record->recorded_at->format('Y-m-d H:i:s'));
    }

    private function machine(): Machine
    {
        return Machine::create([
            'code' => 'TEST-MACHINE-'.uniqid(),
            'name' => 'Mesin Uji Analitik',
            'status' => 'Normal',
            'rated_kw' => 8.5,
            'target_oee' => 80,
            'simulated' => true,
        ]);
    }

    private function energy(Machine $machine, string $eventId, string $timestamp, float $kwh, float $cost, string $source = 'simulation'): EnergyRecord
    {
        return EnergyRecord::create([
            'machine_id' => $machine->id,
            'external_event_id' => $eventId,
            'kwh' => $kwh,
            'tariff_per_kwh' => 2,
            'cost' => $cost,
            'recorded_at' => CarbonImmutable::parse($timestamp),
            'source' => $source,
            'payload' => ['simulated' => $source === 'simulation'],
        ]);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Analytics Test User',
            'email' => uniqid('analytics-').'@example.test',
            'password' => Hash::make('password'),
        ]);
    }
}
