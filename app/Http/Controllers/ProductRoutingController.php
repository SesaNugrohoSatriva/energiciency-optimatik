<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductRouting;
use App\Models\RoutingOperation;
use App\Models\Process;
use App\Services\ProductRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductRoutingController extends Controller
{
    public function store(Request $request, Product $product, ProductRoutingService $service): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160']]);
        $routing = $service->createDraft($product, $data['name']);

        return redirect()->route('products.routings.show', [$product, $routing])->with('success', 'Draft routing versi '.$routing->version.' berhasil dibuat.');
    }

    public function show(Product $product, ProductRouting $routing): View
    {
        abort_unless($routing->product_id === $product->id, 404);
        $routing->load(['operations.process.machine', 'operations.machine']);
        $processes = Process::with('machine')->orderBy('name')->get();
        $machines = Machine::orderBy('name')->get();
        $pageTitle = $product->name.' · Routing v'.$routing->version;
        $pageDescription = 'Urutan proses dan kebutuhan operasi untuk produk ini.';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Products', 'url' => route('products.index')],
            ['label' => $product->name, 'url' => route('products.show', $product)],
            ['label' => 'Routing v'.$routing->version],
        ];

        return view('products.routing', compact('product', 'routing', 'processes', 'machines', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function addOperation(Request $request, Product $product, ProductRouting $routing, ProductRoutingService $service): RedirectResponse
    {
        abort_unless($routing->product_id === $product->id, 404);
        $service->assertDraft($routing);
        $data = $request->validate([
            'sequence' => ['required', 'integer', 'min:1'],
            'process_id' => ['required', 'integer', 'exists:processes,id'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'name' => ['required', 'string', 'max:160'],
            'setup_time_minutes' => ['required', 'integer', 'min:0'],
            'ideal_cycle_seconds' => ['nullable', 'numeric', 'min:0'],
            'labor_required' => ['required', 'integer', 'min:1'],
            'estimated_energy_kwh' => ['required', 'numeric', 'min:0'],
            'work_instructions' => ['nullable', 'string'],
            'requires_quality_inspection' => ['sometimes', 'boolean'],
            'is_optional' => ['sometimes', 'boolean'],
        ]);

        $process = Process::findOrFail($data['process_id']);
        if ($data['machine_id'] && $process->machine_id && (int) $data['machine_id'] !== (int) $process->machine_id) {
            throw ValidationException::withMessages(['machine_id' => 'Mesin yang dipilih tidak sesuai dengan mesin/workstation proses ini.']);
        }
        if ($routing->operations()->where('sequence', $data['sequence'])->exists()) {
            throw ValidationException::withMessages(['sequence' => 'Nomor urutan sudah digunakan pada routing ini.']);
        }

        $data['requires_quality_inspection'] = $request->boolean('requires_quality_inspection');
        $data['is_optional'] = $request->boolean('is_optional');
        $service->addOperation($routing, $data);

        return back()->with('success', 'Operasi berhasil ditambahkan.');
    }

    public function updateOperation(Request $request, Product $product, ProductRouting $routing, RoutingOperation $operation, ProductRoutingService $service): RedirectResponse
    {
        abort_unless($routing->product_id === $product->id && $operation->product_routing_id === $routing->id, 404);
        $service->assertDraft($routing);
        $data = $request->validate([
            'sequence' => ['required', 'integer', 'min:1'],
            'process_id' => ['required', 'integer', 'exists:processes,id'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'name' => ['required', 'string', 'max:160'],
            'setup_time_minutes' => ['required', 'integer', 'min:0'],
            'ideal_cycle_seconds' => ['nullable', 'numeric', 'min:0'],
            'labor_required' => ['required', 'integer', 'min:1'],
            'estimated_energy_kwh' => ['required', 'numeric', 'min:0'],
            'work_instructions' => ['nullable', 'string'],
            'requires_quality_inspection' => ['sometimes', 'boolean'],
            'is_optional' => ['sometimes', 'boolean'],
        ]);

        $process = Process::findOrFail($data['process_id']);
        if ($data['machine_id'] && $process->machine_id && (int) $data['machine_id'] !== (int) $process->machine_id) {
            throw ValidationException::withMessages(['machine_id' => 'Mesin yang dipilih tidak sesuai dengan mesin/workstation proses ini.']);
        }
        if ((int) $data['sequence'] !== (int) $operation->sequence) {
            throw ValidationException::withMessages(['sequence' => 'Gunakan pengaturan urutan operasi untuk mengubah posisi tahap.']);
        }
        $data['requires_quality_inspection'] = $request->boolean('requires_quality_inspection');
        $data['is_optional'] = $request->boolean('is_optional');
        $service->updateOperation($routing, $operation->id, $data);

        return back()->with('success', 'Operasi routing berhasil diperbarui.');
    }

    public function reorder(Request $request, Product $product, ProductRouting $routing, ProductRoutingService $service): RedirectResponse
    {
        abort_unless($routing->product_id === $product->id, 404);
        $data = $request->validate(['operations' => ['required', 'array', 'min:1'], 'operations.*' => ['required', 'integer', 'distinct', 'exists:routing_operations,id']]);
        $service->reorder($routing, $data['operations']);

        return back()->with('success', 'Urutan operasi berhasil diperbarui.');
    }

    public function destroyOperation(Product $product, ProductRouting $routing, RoutingOperation $operation, ProductRoutingService $service): RedirectResponse
    {
        abort_unless($routing->product_id === $product->id && $operation->product_routing_id === $routing->id, 404);
        $service->assertDraft($routing);
        $service->deleteOperation($routing, $operation->id);

        return back()->with('success', 'Operasi dihapus dari draft routing.');
    }

    public function activate(Product $product, ProductRouting $routing, ProductRoutingService $service): RedirectResponse
    {
        abort_unless($routing->product_id === $product->id, 404);
        $service->activate($routing);

        return redirect()->route('products.routings.show', [$product, $routing])->with('success', 'Routing versi '.$routing->version.' kini aktif.');
    }
}
