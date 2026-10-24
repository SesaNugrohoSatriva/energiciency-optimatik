@extends('layouts.app')

@section('content')
    @php
        $tabLabels = [
            'overview' => 'Overview',
            'routing' => 'Routing',
            'history' => 'Production history',
            'cost' => 'Cost breakdown',
            'energy' => 'Energy analysis',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ $product->sku }}</span>
                <x-ui.status-badge :status="$product->is_active ? 'Active' : 'Inactive'" context="boolean" />
                @if (str_contains(strtolower((string) $product->simulation_status), 'simulasi'))
                    <x-ui.status-badge status="simulation" />
                @endif
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $product->category ?? 'Tanpa kategori' }} · {{ $product->base_unit }} · Batch standar {{ $product->batch_size }}</p>
        </div>
        <x-ui.button :href="route('products.edit', $product)">Edit produk</x-ui.button>
    </div>

    <nav class="mb-5 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Tab detail produk">
        @foreach ($tabLabels as $tabKey => $label)
            <a href="{{ route('products.show', [$product, 'tab' => $tabKey]) }}" @if($tab === $tabKey) aria-current="page" @endif class="shrink-0 border-b-2 px-3 py-3 text-xs font-semibold sm:px-4 sm:text-sm {{ $tab === $tabKey ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-navy-900' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if (!$activeRouting || $activeRouting->operations->isEmpty())
        <x-ui.next-action class="mb-5" tone="warning">
            <p>{{ $activeRouting ? 'Routing aktif belum memiliki operasi.' : 'Produk belum memiliki routing aktif.' }} Susun operasi routing berurutan sebelum membuat batch produksi.</p>
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('products.show', [$product, 'tab' => 'routing']) }}">Buka pengelolaan routing</a>
        </x-ui.next-action>
    @else
        <x-ui.next-action class="mb-5" tone="success">
            <p>Routing aktif v{{ $activeRouting->version }} tersedia dengan {{ $activeRouting->operations->count() }} operasi. Order baru dapat mengambil snapshot routing ini.</p>
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('production-orders.create', ['product_id' => $product->id]) }}">Buat order produksi</a>
        </x-ui.next-action>
    @endif

    @if ($tab === 'overview')
        <div class="grid gap-5 xl:grid-cols-3">
            <article class="app-card p-5 sm:p-6 xl:col-span-2">
                <h2 class="text-base font-bold text-navy-900">Informasi produk</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $product->description ?: 'Deskripsi produk belum diisi.' }}</p>
                @if ($product->simulation_status)
                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-900"><strong>Keterangan data:</strong> {{ $product->simulation_status }}</p>
                @endif
                <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-500">Target OEE</dt><dd class="mt-1 text-sm font-bold text-navy-900">{{ number_format((float)$product->target_oee, 1, ',', '.') }}%</dd></div>
                    <div><dt class="text-xs text-slate-500">Estimasi durasi</dt><dd class="mt-1 text-sm font-bold text-navy-900">{{ $product->estimated_production_minutes === null ? 'Belum ditentukan' : number_format($product->estimated_production_minutes).' menit' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Estimasi energi</dt><dd class="mt-1 text-sm font-bold text-navy-900">{{ $product->estimated_energy_kwh === null ? 'Belum ditentukan' : number_format((float)$product->estimated_energy_kwh, 2, ',', '.').' kWh' }}</dd></div>
                </dl>
            </article>
            <div class="space-y-5">
                <article class="app-card p-5">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-bold text-navy-900">Routing aktif</h2><a href="{{ route('products.show',[$product,'tab'=>'routing']) }}" class="text-xs font-semibold text-brand-700">Kelola</a></div>
                    @if ($activeRouting)
                        <a href="{{ route('products.routings.show',[$product,$activeRouting]) }}" class="mt-3 block text-sm font-semibold text-brand-700">{{ $activeRouting->name }} · v{{ $activeRouting->version }}</a>
                        <p class="mt-1 text-xs text-slate-500">{{ $activeRouting->operations->count() }} operasi · {{ $routingEstimate['minutes'] }} menit setup · {{ number_format($routingEstimate['energy'],2,',','.') }} kWh estimasi</p>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Belum ada routing aktif.</p>
                    @endif
                </article>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <x-ui.kpi-card label="Order produksi" :value="number_format($product->productionOrders->count())" detail="Riwayat order produk" icon="O" />
            <x-ui.kpi-card label="Energi tertaut order" :value="number_format((float)$energySummary->kwh,2,',','.').' kWh'" detail="Hanya catatan dengan production_order_id terkait" icon="E" tone="blue" />
            <x-ui.kpi-card label="Biaya energi tertaut" :value="'Rp '.number_format((float)$energySummary->cost,0,',','.')" detail="Nilai dari catatan energi tersimpan" icon="Rp" tone="navy" />
        </div>
    @elseif ($tab === 'routing')
        <div class="grid gap-5 xl:grid-cols-3">
            <article class="app-card p-5 sm:p-6 xl:col-span-2">
                <h2 class="text-base font-bold text-navy-900">Versi routing</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($product->routings->sortByDesc('version') as $routing)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-100 p-3">
                            <div><a href="{{ route('products.routings.show',[$product,$routing]) }}" class="text-sm font-bold text-brand-700 hover:text-brand-900">{{ $routing->name }} · v{{ $routing->version }}</a><p class="mt-1 text-xs text-slate-500">{{ $routing->operations->count() }} operasi · {{ $routing->created_at->format('d M Y') }}</p></div>
                            <x-ui.status-badge :status="$routing->status" context="structure" />
                        </div>
                    @empty
                        <x-ui.empty-state title="Produk belum memiliki routing" description="Buat draft routing, lalu tambahkan proses dalam urutan operasional yang benar." />
                    @endforelse
                </div>
            </article>
            <article class="app-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-navy-900">Buat versi draft</h2>
                <p class="mt-1 text-xs text-slate-500">Jika routing aktif tersedia, operasi akan disalin ke versi baru. Versi aktif tidak diubah.</p>
                <form method="POST" action="{{ route('products.routings.store',$product) }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="routing-name" class="block text-xs font-semibold text-slate-700">Nama routing</label>
                    <input id="routing-name" name="name" required maxlength="160" class="app-input" placeholder="Routing standar">
                    @error('name')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                    <x-ui.button type="submit">Buat draft routing</x-ui.button>
                </form>
            </article>
        </div>
    @elseif ($tab === 'history')
        <x-ui.table>
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Rencana</th><th class="px-4 py-3 text-right">Aktual</th><th class="px-4 py-3 text-right">Baik</th><th class="px-4 py-3">Jadwal</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr><th scope="row" class="px-4 py-3 text-left font-semibold"><a href="{{ route('production-orders.show', $order) }}" class="text-brand-700 hover:text-brand-900">{{ $order->order_number }}</a></th><td class="px-4 py-3"><x-ui.status-badge :status="$order->status" context="production-order" /></td><td class="px-4 py-3 text-right">{{ number_format($order->planned_quantity) }}</td><td class="px-4 py-3 text-right">{{ number_format($order->actual_quantity) }}</td><td class="px-4 py-3 text-right">{{ number_format($order->good_quantity) }}</td><td class="px-4 py-3">{{ $order->scheduled_at?->format('d M Y') ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="p-0"><x-ui.empty-state title="Belum ada order produk" description="Order produksi untuk produk ini akan tercatat di sini." /></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <div class="mt-4">{{ $orders->links() }}</div>
    @elseif ($tab === 'cost')
        <div class="mb-5"><h2 class="text-base font-bold text-navy-900">Cost breakdown</h2><p class="mt-1 text-xs text-slate-500">Estimasi master produk; bukan biaya aktual batch. Biaya material diisi sebagai estimasi produk, bukan dihitung dari daftar komponen.</p></div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.kpi-card label="Material estimasi" :value="'Rp '.number_format((float)$product->material_cost,0,',','.')" detail="Nilai estimasi master produk" icon="M" tone="blue" />
            <x-ui.kpi-card label="Tenaga kerja master" :value="'Rp '.number_format((float)$product->labor_cost,0,',','.')" detail="Nilai master produk" icon="L" />
            <x-ui.kpi-card label="Energi order tertaut" :value="'Rp '.number_format((float)$energySummary->cost,0,',','.')" detail="Akumulasi historis catatan terkait order" icon="E" tone="green" />
            <x-ui.kpi-card label="Overhead master" :value="'Rp '.number_format((float)$product->overhead_cost,0,',','.')" detail="Nilai master produk" icon="O" tone="amber" />
        </div>
    @elseif ($tab === 'energy')
        <div class="mb-5 grid gap-3 sm:grid-cols-2">
            <x-ui.kpi-card label="Energi tertaut" :value="number_format((float)$energySummary->kwh,2,',','.').' kWh'" detail="Hanya order produk ini; sumber simulation/IoT tetap tersimpan per record" icon="E" tone="blue" />
            <x-ui.kpi-card label="Pembacaan terbaru" :value="$energySummary->last_recorded_at ? \Illuminate\Support\Carbon::parse($energySummary->last_recorded_at)->format('d M Y H:i') : 'Belum ada'" detail="Waktu catatan energi terakhir yang tertaut" icon="T" />
        </div>
        <x-ui.table>
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mesin</th><th class="px-4 py-3 text-right">Energi (kWh)</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($energyByMachine as $row)
                    <tr><th scope="row" class="px-4 py-3 text-left font-semibold">{{ $row->machine }}</th><td class="px-4 py-3 text-right">{{ number_format($row->kwh,2,',','.') }}</td></tr>
                @empty
                    <tr><td colspan="2" class="p-0"><x-ui.empty-state title="Belum ada energi tertaut ke order produk" description="Data energi yang tidak memiliki production order terkait tidak dialokasikan secara asumtif." /></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    @endif
@endsection
