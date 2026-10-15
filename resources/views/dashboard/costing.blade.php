@extends('layouts.app')

@section('content')
    <x-ui.alert type="info" class="mb-5">
        Material dihitung dari BOM, energi dari pembacaan yang ditautkan ke order produk, serta tenaga kerja dan overhead dari master produk. Energi tanpa relasi order tidak dialokasikan ke produk.
    </x-ui.alert>

    <x-ui.table>
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th scope="col" class="px-4 py-3.5">Produk</th>
                <th scope="col" class="px-4 py-3.5 text-right">Material</th>
                <th scope="col" class="px-4 py-3.5 text-right">Tenaga kerja</th>
                <th scope="col" class="px-4 py-3.5 text-right">Energi</th>
                <th scope="col" class="px-4 py-3.5 text-right">Overhead</th>
                <th scope="col" class="px-4 py-3.5 text-right">Total estimasi</th>
                <th scope="col" class="px-4 py-3.5 text-right">Estimasi / unit</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($products as $product)
                <tr class="hover:bg-slate-50/80">
                    <th scope="row" class="whitespace-nowrap px-4 py-4 text-left">
                        <span class="block font-bold text-navy-900">{{ $product->name }}</span>
                        <span class="mt-1 block text-xs font-normal text-slate-500">{{ $product->sku }} · batch {{ $product->batch_size }}</span>
                    </th>
                    <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums text-slate-600">Rp {{ number_format($product->costing_material, 0, ',', '.') }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums text-slate-600">Rp {{ number_format($product->costing_labor, 0, ',', '.') }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums text-slate-600">Rp {{ number_format($product->costing_energy, 0, ',', '.') }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums text-slate-600">Rp {{ number_format($product->costing_overhead, 0, ',', '.') }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-right font-bold tabular-nums text-navy-900">Rp {{ number_format($product->costing_total, 0, ',', '.') }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-right font-bold tabular-nums text-brand-700">Rp {{ number_format($product->costing_unit, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-0"><x-ui.empty-state title="Belum ada produk untuk dihitung" description="Tambahkan produk dan komponen BOM terlebih dahulu." /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <section class="mt-8">
        <div class="mb-4">
            <h2 class="text-base font-bold text-navy-900">Biaya aktual dari batch</h2>
            <p class="mt-1 text-xs text-slate-500">Agregat transaksi konsumsi, pembacaan energi yang ditautkan langsung ke batch, serta entri labor/overhead. Tidak mencakup data order-level yang belum dialokasikan.</p>
        </div>
        <x-ui.table>
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr><th scope="col" class="px-4 py-3.5">Produk</th><th scope="col" class="px-4 py-3.5 text-right">Batch berjalan/selesai</th><th scope="col" class="px-4 py-3.5 text-right">Material</th><th scope="col" class="px-4 py-3.5 text-right">Energi</th><th scope="col" class="px-4 py-3.5 text-right">Labor</th><th scope="col" class="px-4 py-3.5 text-right">Overhead</th><th scope="col" class="px-4 py-3.5 text-right">Total aktual</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr>
                        <th scope="row" class="whitespace-nowrap px-4 py-4 text-left"><span class="block font-bold text-navy-900">{{ $product->name }}</span><span class="mt-1 block text-xs font-normal text-slate-500">{{ $product->sku }}</span></th>
                        <td class="px-4 py-4 text-right">{{ number_format($product->actual_batch_count) }}</td>
                        <td class="px-4 py-4 text-right">Rp {{ number_format($product->actual_material_cost,0,',','.') }}</td>
                        <td class="px-4 py-4 text-right">Rp {{ number_format($product->actual_energy_cost,0,',','.') }}</td>
                        <td class="px-4 py-4 text-right">Rp {{ number_format($product->actual_labor_cost,0,',','.') }}</td>
                        <td class="px-4 py-4 text-right">Rp {{ number_format($product->actual_overhead_cost,0,',','.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-navy-900">Rp {{ number_format($product->actual_cost_total,0,',','.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-0"><x-ui.empty-state title="Belum ada produk" description="Data aktual akan dihitung setelah transaksi batch tersedia." /></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </section>
@endsection
