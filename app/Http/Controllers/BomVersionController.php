<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Product;
use App\Services\BomVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class BomVersionController extends Controller
{
    public function store(Product $product, BomVersionService $service): RedirectResponse
    {
        $bom = $service->createDraft($product);

        return redirect()->route('bom-items.create', ['product_id' => $product->id, 'bom_id' => $bom->id])
            ->with('success', 'Draft BOM ' . $bom->bom_number . ' dibuat. Periksa pemetaan inventory dan komponen sebelum aktivasi.');
    }

    public function activate(Product $product, Bom $bom, BomVersionService $service): RedirectResponse
    {
        abort_unless($bom->product_id === $product->id, 404);
        if ($bom->status !== 'draft') {
            throw ValidationException::withMessages(['bom' => 'Hanya draft BOM yang dapat diaktifkan.']);
        }
        $service->activate($bom);

        return redirect()->route('products.show', [$product, 'tab' => 'bom'])->with('success', 'BOM versi ' . $bom->version . ' kini aktif.');
    }
}
