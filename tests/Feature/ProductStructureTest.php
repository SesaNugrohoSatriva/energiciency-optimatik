<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Machine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\ProductionOrderStructureService;
use Illuminate\Support\Facades\Hash;
use App\Services\ProductionBatchService;
use App\Services\InventoryReservationService;
use App\Services\InventoryTransactionService;
use App\Services\BomVersionService;
use App\Services\ProductRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_draft_copies_active_components_and_activation_archives_previous_version(): void
    {
        $product = Product::create(['sku' => 'TEST-001', 'name' => 'Test product']);
        $activeBom = $product->boms()->create([
            'bom_number' => 'TEST-001-BOM-001',
            'version' => 1,
            'status' => 'active',
            'output_quantity' => 1,
        ]);
        $activeBom->items()->create([
            'product_id' => $product->id,
            'component_sku' => 'COMP-001',
            'component_name' => 'Test component',
            'quantity_per_unit' => 2,
            'unit' => 'pcs',
            'unit_cost' => 12.5,
            'scrap_allowance_percent' => 3,
        ]);

        $service = app(BomVersionService::class);
        $draft = $service->createDraft($product->fresh());

        $this->assertSame('draft', $draft->status);
        $this->assertSame(1, $draft->items()->count());
        $this->assertNotSame($activeBom->items()->first()->id, $draft->items()->first()->id);
        $this->assertSame('COMP-001', $draft->items()->first()->component_sku);

        $service->activate($draft);

        $this->assertSame('archived', $activeBom->fresh()->status);
        $this->assertSame('active', $draft->fresh()->status);
    }

    public function test_routing_draft_copies_operations_reorders_and_activates_without_mutating_previous_version(): void
    {
        $product = Product::create(['sku' => 'TEST-002', 'name' => 'Test product']);
        $machine = Machine::create(['code' => 'TEST-M-1', 'name' => 'Test machine']);
        $process = Process::create(['code' => 'TEST-P-1', 'name' => 'Test process']);
        $activeRouting = $product->routings()->create([
            'version' => 1,
            'name' => 'Initial route',
            'status' => 'active',
        ]);
        $activeOperation = $activeRouting->operations()->create([
            'sequence' => 1,
            'process_id' => $process->id,
            'machine_id' => $machine->id,
            'name' => 'Initial operation',
            'setup_time_minutes' => 5,
            'labor_required' => 1,
            'estimated_energy_kwh' => 2,
        ]);

        $service = app(ProductRoutingService::class);
        $draft = $service->createDraft($product->fresh(), 'Updated route');
        $copiedOperation = $draft->operations()->firstOrFail();
        $service->addOperation($draft, [
            'sequence' => 2,
            'process_id' => $process->id,
            'machine_id' => $machine->id,
            'name' => 'Second operation',
            'setup_time_minutes' => 3,
            'labor_required' => 1,
            'estimated_energy_kwh' => 1,
        ]);
        $newOperation = $draft->operations()->where('sequence', 2)->firstOrFail();

        $service->reorder($draft, [$newOperation->id, $copiedOperation->id]);
        $this->assertSame([$newOperation->id, $copiedOperation->id], $draft->fresh()->operations()->pluck('id')->all());

        $service->activate($draft);

        $this->assertSame('archived', $activeRouting->fresh()->status);
        $this->assertSame('active', $draft->fresh()->status);
        $this->assertSame(1, (int) $activeOperation->fresh()->sequence);
        $this->assertSame('Initial operation', $activeOperation->fresh()->name);
    }

    public function test_active_bom_cannot_be_reactivated_through_version_service(): void
    {
        $product = Product::create(['sku' => 'TEST-003', 'name' => 'Test product']);
        $activeBom = $product->boms()->create([
            'bom_number' => 'TEST-003-BOM-001',
            'version' => 1,
            'status' => 'active',
            'output_quantity' => 1,
        ]);
        $activeBom->items()->create([
            'product_id' => $product->id,
            'component_sku' => 'COMP-003',
            'component_name' => 'Test component',
            'quantity_per_unit' => 1,
            'unit' => 'pcs',
            'unit_cost' => 10,
        ]);

        $this->expectException(ValidationException::class);
        app(BomVersionService::class)->activate($activeBom);
    }

    public function test_batches_enforce_order_quantity_capture_snapshots_and_roll_up_completed_output(): void
    {
        $inventory = InventoryItem::create([
            'sku' => 'INV-TEST-004',
            'name' => 'Test component',
            'quantity' => 20,
            'threshold' => 2,
            'unit' => 'pcs',
            'unit_cost' => 5,
        ]);
        $bomSnapshot = [
            'bom_number' => 'TEST-004-BOM-001',
            'version' => 1,
            'output_quantity' => 1,
            'items' => [[
                'component_sku' => $inventory->sku,
                'component_name' => $inventory->name,
                'inventory_item_id' => $inventory->id,
                'quantity_per_unit' => 1,
                'unit' => 'pcs',
                'unit_cost' => 5,
            ]],
        ];
        $order = ProductionOrder::create([
            'order_number' => 'PO-TEST-004',
            'product_id' => Product::create(['sku' => 'TEST-004', 'name' => 'Test product'])->id,
            'status' => 'Planned',
            'planned_quantity' => 9,
            'actual_quantity' => 1,
            'good_quantity' => 1,
            'baseline_actual_quantity' => 1,
            'baseline_good_quantity' => 1,
            'bom_snapshot' => $bomSnapshot,
            'routing_snapshot' => ['name' => 'Test route', 'version' => 1, 'operations' => [['sequence' => 1, 'name' => 'Assembly']]],
        ]);
        $service = app(ProductionBatchService::class);
        $first = $service->create($order, 5, null, null);

        $this->assertSame($bomSnapshot, $first->bom_snapshot);
        $this->assertSame('planned', $first->status);

        try {
            $service->create($order, 4, null, null);
            $this->fail('The order must not accept batches above its planned quantity.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('planned_quantity', $exception->errors());
        }

        $inventoryService = app(InventoryReservationService::class);
        $inventoryService->reserve($first, null);
        $this->assertEquals(20.0, (float) $inventory->fresh()->quantity);
        $service->start($first, null);
        try {
            $service->complete($first, 6, 5, null, null);
            $this->fail('Actual output must not exceed planned batch quantity.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actual_quantity', $exception->errors());
        }
        $inventoryService->consume($first, [$first->materials()->firstOrFail()->id => 5], null);
        $service->complete($first, 5, 4, null, null);

        $second = $service->create($order->fresh(), 3, null, null);
        $inventoryService->reserve($second, null);
        $service->start($second, null);
        $inventoryService->consume($second, [$second->materials()->firstOrFail()->id => 3], null);
        $service->complete($second, 3, 3, null, null);

        $this->assertSame(9, (int) $order->fresh()->actual_quantity);
        $this->assertSame(8, (int) $order->fresh()->good_quantity);
        $this->assertSame('Completed', $order->fresh()->status);
        $this->assertSame(3, $first->events()->count());
        $this->assertEquals(12.0, (float) $inventory->fresh()->quantity);
        $this->assertSame(4, $inventory->transactions()->count());
    }

    public function test_batch_cannot_start_without_routing_operations(): void
    {
        $product = Product::create(['sku' => 'TEST-ROUTE-REQUIRED', 'name' => 'Routing required product']);
        $order = ProductionOrder::create([
            'order_number' => 'PO-ROUTE-REQUIRED',
            'product_id' => $product->id,
            'status' => 'Planned',
            'planned_quantity' => 2,
            'actual_quantity' => 0,
            'good_quantity' => 0,
            'baseline_actual_quantity' => 0,
            'baseline_good_quantity' => 0,
            'bom_snapshot' => ['output_quantity' => 1, 'items' => []],
            'routing_snapshot' => ['name' => 'Empty route', 'version' => 1, 'operations' => []],
        ]);
        $service = app(ProductionBatchService::class);
        $batch = $service->create($order, 2, null, null);

        try {
            $service->start($batch, null);
            $this->fail('A batch must not start without routing operations.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('batch', $exception->errors());
        }

        $this->assertSame('planned', $batch->fresh()->status);
    }

    public function test_authenticated_batch_workflow_pages_and_cost_entry_route_work_end_to_end(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'batch-workflow@example.test',
            'password' => Hash::make('password'),
        ]);
        $product = Product::create(['sku' => 'TEST-005', 'name' => 'Batch workflow product']);
        $inventory = InventoryItem::create([
            'sku' => 'INV-TEST-005',
            'name' => 'Mapped material',
            'quantity' => 10,
            'threshold' => 1,
            'unit' => 'pcs',
            'unit_cost' => 25,
        ]);
        $bom = $product->boms()->create([
            'bom_number' => 'TEST-005-BOM-001',
            'version' => 1,
            'status' => 'active',
            'output_quantity' => 1,
        ]);
        $bom->items()->create([
            'product_id' => $product->id,
            'inventory_item_id' => $inventory->id,
            'component_sku' => $inventory->sku,
            'component_name' => $inventory->name,
            'quantity_per_unit' => 1,
            'unit' => 'pcs',
            'unit_cost' => 25,
        ]);
        $process = Process::create(['code' => 'TEST-P-5', 'name' => 'Batch process']);
        $routing = $product->routings()->create(['version' => 1, 'name' => 'Batch route', 'status' => 'active']);
        $routing->operations()->create([
            'sequence' => 1,
            'process_id' => $process->id,
            'name' => 'Assembly operation',
            'labor_required' => 1,
            'estimated_energy_kwh' => 1,
        ]);
        $order = new ProductionOrder([
            'order_number' => 'PO-TEST-005',
            'product_id' => $product->id,
            'status' => 'Planned',
            'planned_quantity' => 2,
            'actual_quantity' => 0,
            'good_quantity' => 0,
        ]);
        $order->fill(app(ProductionOrderStructureService::class)->capture($order));
        $order->save();

        $this->actingAs($user)
            ->get(route('production-orders.show', $order))
            ->assertOk()
            ->assertSee('Snapshot struktur order')
            ->assertSee('Struktur order sudah tersedia')
            ->assertSee('Order direncanakan');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('BOM aktif v1')
            ->assertSee('Buat order produksi');

        $this->post(route('production-orders.batches.store', $order), ['planned_quantity' => 2])
            ->assertRedirect();
        $batch = $order->batches()->firstOrFail();
        $this->get(route('production-orders.batches.show', [$order, $batch]))
            ->assertOk()
            ->assertSee('Kebutuhan dan transaksi material')
            ->assertSee('Reservasikan seluruh kebutuhan material');

        $this->patch(route('production-orders.batches.materials.reserve', [$order, $batch]))->assertRedirect();
        $this->patch(route('production-orders.batches.start', [$order, $batch]))->assertRedirect();
        $this->patch(route('production-orders.batches.materials.consume', [$order, $batch]), [
            'material_id' => $batch->materials()->firstOrFail()->id,
            'quantity' => 2,
        ])->assertRedirect();
        $this->patch(route('production-orders.batches.complete', [$order, $batch]), [
            'actual_quantity' => 2,
            'good_quantity' => 2,
        ])->assertRedirect();
        $this->post(route('production-orders.batches.costs.store', [$order, $batch]), [
            'category' => 'labor',
            'amount' => 75,
            'description' => 'Operator actual cost',
        ])->assertRedirect();

        $this->get(route('production-orders.batches.show', [$order, $batch]))
            ->assertOk()
            ->assertSee('Operator actual cost')
            ->assertSee('Batch selesai.');
        $this->get(route('inventory.show', $inventory))
            ->assertOk()
            ->assertSee('Ledger inventory');
        $this->get(route('energy-records.create'))
            ->assertOk()
            ->assertSee('Batch produksi terkait')
            ->assertSee('data simulasi, bukan pembacaan sensor aktual');
        $this->get(route('oee-records.create'))
            ->assertOk()
            ->assertSee('Operasi routing terkait')
            ->assertSee('Waktu siklus ideal per unit dalam menit');
        $this->get(route('costing'))->assertOk()->assertSee('Biaya aktual dari batch');
        $this->get(route('dashboard'))->assertOk();
        $this->assertEquals(8.0, (float) $inventory->fresh()->quantity);
        $this->assertSame('Completed', $order->fresh()->status);
    }

    public function test_inventory_opening_and_manual_adjustments_are_logged_idempotently(): void
    {
        $inventory = InventoryItem::create([
            'sku' => 'INV-TEST-006',
            'name' => 'Opening balance material',
            'quantity' => 5,
            'threshold' => 1,
            'unit' => 'pcs',
            'unit_cost' => 10,
        ]);
        $service = app(InventoryTransactionService::class);

        $service->recordOpeningBalance($inventory);
        $service->recordOpeningBalance($inventory);
        $service->setOnHand($inventory, 7, null);

        $this->assertSame(1, $inventory->transactions()->where('transaction_type', 'opening_balance')->count());
        $this->assertSame(1, $inventory->transactions()->where('transaction_type', 'adjustment')->count());
        $this->assertEquals(2.0, (float) $inventory->transactions()->where('transaction_type', 'adjustment')->value('quantity'));
        $this->assertEquals(7.0, (float) $inventory->fresh()->quantity);
    }
}
