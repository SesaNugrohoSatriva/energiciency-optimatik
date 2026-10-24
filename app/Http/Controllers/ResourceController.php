<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Machine,Process,Product,ProductionOrder,InventoryItem,MaintenanceLog,EnergyRecord};
use App\Services\ProductionOrderStructureService;
class ResourceController extends Controller
{
    private array $map = ['machines' => [Machine::class, 'Mesin', 'machine_fields'], 'processes' => [Process::class, 'Proses', 'process_fields'], 'products' => [Product::class, 'Produk', 'product_fields'], 'production-orders' => [ProductionOrder::class, 'Order Produksi', 'order_fields'], 'inventory' => [InventoryItem::class, 'Persediaan', 'inventory_fields'], 'maintenance-logs' => [MaintenanceLog::class, 'Pemeliharaan', 'maintenance_fields'], 'energy-records' => [EnergyRecord::class, 'Data Energi', 'energy_fields'], 'oee-records' => [\App\Models\OeeRecord::class, 'Catatan OEE', 'oee_fields']];

    private function config(Request $r): array
    {
        $key = $r->route()->getName();
        $key = preg_replace('/\.(index|create|store|show|edit|update|destroy)$/', '', $key);
        abort_unless(isset($this->map[$key]), 404);

        return [$key, ...$this->map[$key]];
    }

    public function index(Request $request)
    {
        [$key,$class,$label] = $this->config($request);
        $searchColumns = [
            'machines' => ['code', 'name', 'category', 'location', 'device_id'], 'processes' => ['code', 'name', 'description'],
            'products' => ['sku', 'name', 'category', 'description'], 'production-orders' => ['order_number', 'status'],
            'inventory' => ['sku', 'name', 'category', 'location'], 'maintenance-logs' => ['issue', 'priority', 'status'],
            'energy-records' => ['device_id', 'external_event_id', 'source'],
            'oee-records' => ['notes']
        ];
        $sortColumns = [
            'machines' => ['name', 'code', 'status', 'rated_kw'], 'processes' => ['name', 'code', 'estimated_kwh'],
            'products' => ['name', 'sku', 'batch_size'], 'production-orders' => ['order_number', 'status', 'scheduled_at'],
            'inventory' => ['name', 'sku', 'quantity'], 'maintenance-logs' => ['scheduled_at', 'priority', 'status'],
            'energy-records' => ['recorded_at', 'kwh', 'cost', 'source'],
            'oee-records' => ['recorded_on', 'oee']
        ];
        $query = $class::query();
        if ($key === 'energy-records') {
            $query->with(['productionBatch', 'routingOperation']);
        }
        if ($key === 'oee-records') {
            $query->with(['batch', 'routingOperation']);
        }
        $search = trim((string)$request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search, $searchColumns, $key) {
                foreach ($searchColumns[$key] as $column) {
                    $builder->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }
        $filterField = match ($key) {
            'machines','production-orders','maintenance-logs' => 'status','energy-records' => 'source',default => null
        };
        $filter = $request->query('filter');
        $filterOptions = match ($key) {
            'machines' => ['Normal', 'Monitor', 'Down'],
            'production-orders' => ['Planned', 'In Progress', 'Completed', 'Delayed'],
            'maintenance-logs' => ['Open', 'Monitor', 'Planned', 'In Progress', 'Completed'],
            'energy-records' => ['simulation', 'manual', 'iot'],
            default => []
        };
        if ($filterField && in_array($filter, $filterOptions, true)) {
            $query->where($filterField, $filter);
        }
        $sort = $request->query('sort');
        if (!in_array($sort, $sortColumns[$key], true)) {
            $sort = null;
        }
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $records = ($sort ? $query->orderBy($sort, $direction) : $query->latest())->paginate(15)->withQueryString();
        $pageTitle = $label;
        $pageDescription = 'Telusuri dan kelola ' . $label . ' yang tersimpan pada workspace.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $label]];
        $filterLabel = $key === 'energy-records' ? 'sumber data' : 'status';

        return view('crud.index', compact('key', 'label', 'records', 'search', 'filter', 'filterOptions', 'filterLabel', 'sort', 'direction', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function create(Request $request)
    {
        [$key,$class,$label] = $this->config($request);
        $record = new $class;
        $prefillFields = $key === 'energy-records' ? ['machine_id', 'production_order_id', 'production_batch_id'] : ($key === 'maintenance-logs' ? ['machine_id'] : ['product_id']);
        foreach ($prefillFields as $field) {
            if ($request->filled($field)) {
                $record->{
                    $field}
                = $request->query($field);
            }
        }
        $references = $this->references();
        $pageTitle = 'Tambah ' . $label;
        $pageDescription = 'Lengkapi informasi di bawah ini. Field wajib ditandai dengan *. ';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $label, 'url' => route($key . '.index')], ['label' => 'Tambah']];

        return view('crud.form', compact('key', 'label', 'record', 'references', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function show(Request $request, $record)
    {
        [$key,$class,$label] = $this->config($request);
        if ($key === 'products') {
            return $this->showProduct($request, (int)$record);
        }
        if ($key === 'production-orders') {
            return $this->showProductionOrder((int)$record);
        }
        if ($key === 'inventory') {
            return $this->showInventory((int)$record);
        }
        $record = $class::findOrFail($record);
        $pageTitle = $label . ' · Detail';
        $pageDescription = 'Informasi tersimpan pada workspace.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $label, 'url' => route($key . '.index')], ['label' => 'Detail']];

        return view('crud.show', compact('key', 'label', 'record', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    private function showProductionOrder(int $id)
    {
        $record = ProductionOrder::with(['product', 'routing', 'batches.events.user'])->findOrFail($id);
        $remaining = max(0, (int)$record->planned_quantity - (int)$record->baseline_actual_quantity - (int)$record->batches()->where('status', '!=', 'cancelled')->sum('planned_quantity'));
        $pageTitle = 'Order ' . $record->order_number;
        $pageDescription = 'Routing dan progres order produksi.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Production Orders', 'url' => route('production-orders.index')], ['label' => $record->order_number]];

        return view('production-orders.show', compact('record', 'remaining', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    private function showInventory(int $id)
    {
        $record = InventoryItem::findOrFail($id);
        $transactions = $record->transactions()->with('user')->paginate(25)->withQueryString();
        $pageTitle = $record->name;
        $pageDescription = $record->sku . ' · Saldo dan ledger transaksi inventory.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Inventory', 'url' => route('inventory.index')], ['label' => $record->name]];

        return view('inventory.show', compact('record', 'transactions', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    private function showProduct(Request $request, int $id)
    {
        $product = Product::with(['activeRouting.operations.process.machine', 'routings.operations.process.machine', 'productionOrders'])->findOrFail($id);
        $tab = $request->query('tab', 'overview');
        $tabs = ['overview', 'routing', 'history', 'cost', 'energy'];
        abort_unless(in_array($tab, $tabs, true), 404);
        $orders = $product->productionOrders()->latest()->paginate(10, ['*'], 'orders_page')->withQueryString();
        $linkedEnergy = EnergyRecord::join('production_orders', 'energy_records.production_order_id', '=', 'production_orders.id')->where('production_orders.product_id', $product->id);
        $energySummary = (clone $linkedEnergy)->selectRaw('COALESCE(SUM(energy_records.kwh),0) as kwh, COALESCE(SUM(energy_records.cost),0) as cost, MAX(energy_records.recorded_at) as last_recorded_at')->first();
        $energyByMachine = (clone $linkedEnergy)->leftJoin('machines', 'energy_records.machine_id', '=', 'machines.id')->selectRaw("COALESCE(machines.name,'Mesin tidak ditautkan') as machine, SUM(energy_records.kwh) as kwh")->groupBy('machines.name')->orderByDesc('kwh')->get();
        $activeRouting = $product->activeRouting;
        $routingEstimate = ['minutes' => 0, 'energy' => 0];
        if ($activeRouting) {
            foreach ($activeRouting->operations as $operation) {
                $routingEstimate['minutes'] += (int)$operation->setup_time_minutes;
                $routingEstimate['energy'] += (float)$operation->estimated_energy_kwh;
            }
        }
        $pageTitle = $product->name;
        $pageDescription = $product->sku . ' · Detail dan jejak operasional produk.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Products', 'url' => route('products.index')], ['label' => $product->name]];

        return view('products.show', compact('product', 'tab', 'tabs', 'orders', 'energySummary', 'energyByMachine', 'activeRouting', 'routingEstimate', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function store(Request $request, ProductionOrderStructureService $structure)
    {
        [$key,$class,$label,$fields] = $this->config($request);
        $data = $this->validateData($request, $key);
        $data = $this->calculateDerivedFields($key, $data);
        if ($key === 'production-orders') {
            $data['baseline_actual_quantity'] = (int)($data['actual_quantity'] ?? 0);
            $data['baseline_good_quantity'] = (int)($data['good_quantity'] ?? 0);
            $data = array_merge($data, $structure->capture(new ProductionOrder($data), $data['product_routing_id'] ?? null));
        }
        elseif ($key === 'inventory') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $class) {
                $item = $class::create($data); app(\App\Services\InventoryTransactionService::class)->recordOpeningBalance($item);
            });
        }
        else {
            $class::create($data);
        }
        return redirect()->route($key . '.index')->with('success', "$label berhasil ditambahkan.");
    }

    public function edit(Request $request, $record)
    {
        [$key,$class,$label] = $this->config($request);
        $record = $class::findOrFail($record);
        $references = $this->references();
        $pageTitle = 'Edit ' . $label;
        $pageDescription = 'Perbarui informasi tanpa mengubah validasi server.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $label, 'url' => route($key . '.index')], ['label' => 'Edit']];

        return view('crud.form', compact('key', 'label', 'record', 'references', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function update(Request $request, $record, ProductionOrderStructureService $structure)
    {
        [$key,$class,$label,$fields] = $this->config($request);
        $item = $class::findOrFail($record);
        $data = $this->validateData($request, $key, $item->id);
        $data = $this->calculateDerivedFields($key, $data);
        if ($key === 'production-orders') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($item, $data, $structure) {
                $order = ProductionOrder::query()->lockForUpdate()->findOrFail($item->id); if ($order->batches()->exists()) {
                    foreach (['product_id', 'product_routing_id', 'status', 'actual_quantity', 'good_quantity'] as $field) {
                        if ((string)$order->{
                            $field}
                        !== (string)($data[$field] ?? '')) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['production_order' => 'Struktur, status, dan output order yang sudah memiliki batch dikelola melalui workflow batch.']);
                        }
                    }
                    $reserved = (int)$order->baseline_actual_quantity + (int)$order->batches()->where('status', '!=', 'cancelled')->sum('planned_quantity'); if ((int)$data['planned_quantity'] < $reserved) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['planned_quantity' => 'Kuantitas order tidak boleh lebih kecil dari output awal dan total kuantitas batch.']);
                    }
                }
                else {
                    $data['baseline_actual_quantity'] = (int)($data['actual_quantity'] ?? 0); $data['baseline_good_quantity'] = (int)($data['good_quantity'] ?? 0); $data = array_merge($data, $structure->capture(new ProductionOrder($data), $data['product_routing_id'] ?? null));
                }
                $order->update($data);
            });
        }
        elseif ($key === 'inventory') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($item, $data, $request) {
                $inventory = InventoryItem::query()->lockForUpdate()->findOrFail($item->id); if ($inventory->unit !== $data['unit'] && $inventory->transactions()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['unit' => 'Satuan tidak dapat diubah setelah item memiliki transaksi.']);
                }
                $quantity = (float)$data['quantity']; unset($data['quantity']); $inventory->update($data); app(\App\Services\InventoryTransactionService::class)->setOnHand($inventory, $quantity, $request->user()?->id);
            });
        }
        else {
            $item->update($data);
        }
        if ($key === 'production-orders') {
            return redirect()->route('production-orders.show', $item)->with('success', "$label berhasil diperbarui.");
        }
        if ($key === 'inventory') {
            return redirect()->route('inventory.show', $item)->with('success', "$label berhasil diperbarui.");
        }

        return redirect()->route($key . '.index')->with('success', "$label berhasil diperbarui.");
    }

    public function destroy(Request $request, $record)
    {
        [$key,$class,$label,$fields] = $this->config($request);
        $item = $class::findOrFail($record);
        if ($key === 'inventory' && $item->transactions()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['inventory' => 'Item dengan riwayat transaksi inventory tidak dapat dihapus.']);
        }
        else {
            $item->delete();
        }
        return redirect()->route($key . '.index')->with('success', "$label berhasil dihapus.");
    }

    private function calculateDerivedFields(string $key, array $data): array
    {
        if ($key === 'oee-records') {
            $planned = (int)$data['planned_minutes'];
            $operating = (int)$data['operating_minutes'];
            $total = (int)$data['total_output'];
            $good = (int)$data['good_output'];
            $availability = $planned > 0 ? min(1, $operating / $planned) : 0;
            $performance = $operating > 0 ? min(1, ((float)$data['ideal_cycle_minutes'] * $total) / $operating) : 0;
            $quality = $total > 0 ? min(1, $good / $total) : 0;
            $data['availability'] = $availability;
            $data['performance'] = $performance;
            $data['quality'] = $quality;
            $data['oee'] = $availability * $performance * $quality;
        }
        if ($key === 'energy-records') {
            $data['cost'] = round(((float)$data['kwh']) * ((float)$data['tariff_per_kwh']), 2);
        }

        return $data;
    }

    private function references(): array
    {
        return ['machines' => Machine::orderBy('name')->get(['id', 'name', 'code']), 'processes' => Process::orderBy('name')->get(['id', 'name', 'code']), 'products' => Product::orderBy('name')->get(['id', 'name', 'sku']), 'productionOrders' => ProductionOrder::orderBy('order_number')->get(['id', 'order_number']), 'productionBatches' => \App\Models\ProductionBatch::with('productionOrder')->latest()->limit(200)->get(['id', 'production_order_id', 'batch_number']), 'routingOperations' => \App\Models\RoutingOperation::orderBy('product_routing_id')->orderBy('sequence')->limit(500)->get(['id', 'name', 'sequence']), 'productRoutings' => \App\Models\ProductRouting::with('product')->orderBy('product_id')->orderByDesc('version')->get()];
    }

    private function validateData(Request $r, string $key, ?int $id = null): array
    {
        $schemas = [
            'machines' => ['code' => 'required|string|max:50', 'name' => 'required|string|max:160', 'category' => 'nullable|string|max:80', 'location' => 'nullable|string|max:120', 'capacity' => 'nullable|numeric|min:0', 'capacity_unit' => 'nullable|string|max:30', 'rated_kw' => 'nullable|numeric|min:0', 'target_oee' => 'nullable|numeric|min:0|max:100', 'status' => 'required|in:Normal,Monitor,Down', 'device_id' => 'nullable|string|max:120'],
            'processes' => ['code' => 'required|string|max:50', 'name' => 'required|string|max:160', 'machine_id' => 'nullable|exists:machines,id', 'estimated_kwh' => 'nullable|numeric|min:0', 'setup_time_minutes' => 'nullable|integer|min:0', 'ideal_cycle_seconds' => 'nullable|numeric|min:0', 'labor_required' => 'nullable|integer|min:1', 'capacity' => 'nullable|numeric|min:0', 'capacity_unit' => 'nullable|string|max:30', 'work_instructions' => 'nullable|string', 'quality_criteria' => 'nullable|string', 'description' => 'nullable|string'],
            'products' => ['sku' => 'required|string|max:80', 'name' => 'required|string|max:180', 'description' => 'nullable|string', 'category' => 'nullable|string|max:100', 'base_unit' => 'required|string|max:30', 'is_active' => 'required|boolean', 'batch_size' => 'required|integer|min:1', 'target_oee' => 'nullable|numeric|min:0|max:100', 'estimated_production_minutes' => 'nullable|integer|min:0', 'estimated_energy_kwh' => 'nullable|numeric|min:0', 'simulation_status' => 'nullable|string|max:120', 'material_cost' => 'nullable|numeric|min:0', 'labor_cost' => 'nullable|numeric|min:0', 'overhead_cost' => 'nullable|numeric|min:0'],
            'production-orders' => ['order_number' => 'required|string|max:80', 'product_id' => 'required|exists:products,id', 'product_routing_id' => 'nullable|exists:product_routings,id', 'status' => 'required|in:Planned,In Progress,Completed,Delayed', 'planned_quantity' => 'required|integer|min:1', 'actual_quantity' => 'nullable|integer|min:0', 'good_quantity' => 'nullable|integer|min:0|lte:actual_quantity', 'scheduled_at' => 'nullable|date'],
            'inventory' => ['sku' => 'required|string|max:80', 'name' => 'required|string|max:180', 'category' => 'nullable|string|max:100', 'quantity' => 'required|numeric|min:0', 'threshold' => 'required|numeric|min:0', 'unit' => 'required|string|max:20', 'unit_cost' => 'required|numeric|min:0', 'location' => 'nullable|string|max:100'],
            'maintenance-logs' => ['machine_id' => 'required|exists:machines,id', 'issue' => 'required|string|max:200', 'priority' => 'required|in:Low,Medium,High,Critical', 'status' => 'required|in:Open,Monitor,Planned,In Progress,Completed', 'scheduled_at' => 'nullable|date', 'notes' => 'nullable|string'],
            'energy-records' => ['machine_id' => 'nullable|exists:machines,id', 'process_id' => 'nullable|exists:processes,id', 'production_order_id' => 'nullable|exists:production_orders,id', 'production_batch_id' => 'nullable|exists:production_batches,id', 'routing_operation_id' => 'nullable|exists:routing_operations,id', 'device_id' => 'nullable|string|max:120', 'kwh' => 'required|numeric|min:0', 'tariff_per_kwh' => 'required|numeric|min:0', 'recorded_at' => 'required|date', 'source' => 'required|in:simulation,manual,iot', 'external_event_id' => 'nullable|string|max:180'],
            'oee-records' => ['machine_id' => 'required|exists:machines,id', 'production_batch_id' => 'nullable|exists:production_batches,id', 'routing_operation_id' => 'nullable|exists:routing_operations,id', 'recorded_on' => 'required|date', 'planned_minutes' => 'required|integer|min:1', 'operating_minutes' => 'required|integer|min:0|lte:planned_minutes', 'ideal_cycle_minutes' => 'required|numeric|min:0.001', 'total_output' => 'required|integer|min:0', 'good_output' => 'required|integer|min:0|lte:total_output', 'notes' => 'nullable|string']
        ];
        $rules = $schemas[$key];
        $data = $r->validate($rules);
        if (in_array($key, ['energy-records', 'oee-records'], true) && isset($data['production_batch_id'])) {
            $batch = \App\Models\ProductionBatch::findOrFail($data['production_batch_id']);
            if ($key === 'energy-records') {
                if (($data['production_order_id'] ?? null) && (int)$data['production_order_id'] !== (int)$batch->production_order_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['production_batch_id' => 'Batch harus berasal dari order produksi yang dipilih.']);
                }
                $data['production_order_id'] = $batch->production_order_id;
            }
            if (($data['routing_operation_id'] ?? null) && !in_array((int)$data['routing_operation_id'], array_map('intval', array_column($batch->routing_snapshot['operations'] ?? [], 'id')), true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['routing_operation_id' => 'Operasi harus termasuk routing snapshot batch.']);
            }
        }
        elseif (in_array($key, ['energy-records', 'oee-records'], true) && ($data['routing_operation_id'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['routing_operation_id' => 'Tautkan batch untuk memilih operasi routing.']);
        }
        if ($key === 'products') {
            $data['is_active'] = (bool)$data['is_active'];
        }

        return $data;
    }
}
