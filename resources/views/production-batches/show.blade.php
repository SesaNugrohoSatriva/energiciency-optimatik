@extends('layouts.app')

@section('content')
    @php
        $routingSnapshot = $batch->routing_snapshot;
    @endphp

    @if ($batch->status === 'planned' && empty($routingSnapshot['operations']))
        <x-ui.next-action class="mb-5" tone="warning">
            <p>Snapshot routing batch belum memiliki operasi. Periksa routing order dan buat batch baru setelah strukturnya benar.</p>
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('production-orders.show', $productionOrder) }}">Periksa routing order</a>
        </x-ui.next-action>
    @elseif ($batch->status === 'planned')
        <x-ui.next-action class="mb-5">
            <p>Batch siap dimulai menggunakan routing snapshot. Status order akan diperbarui saat batch dimulai.</p>
        </x-ui.next-action>
    @elseif ($batch->status === 'in_progress')
        <x-ui.next-action class="mb-5">
            <p>Catat output aktual dan output baik setelah pekerjaan selesai. Hasil batch akan diperhitungkan pada progres order.</p>
        </x-ui.next-action>
    @elseif ($batch->status === 'completed')
        <x-ui.next-action class="mb-5" tone="success">
            <p>Batch selesai. Output aktual dan output baik tercatat pada order; riwayat routing, energi, dan biaya tetap tersedia.</p>
        </x-ui.next-action>
    @else
        <x-ui.next-action class="mb-5" tone="warning">
            <p>Batch dibatalkan sebelum dimulai. Riwayat event tetap tersedia dan batch ini tidak dipakai untuk memenuhi kuantitas rencana order.</p>
        </x-ui.next-action>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.status-badge :status="$batch->status" context="production-batch" />
            <span class="text-xs text-slate-500">Order <a class="font-semibold text-brand-700" href="{{ route('production-orders.show', $productionOrder) }}">{{ $productionOrder->order_number }}</a></span>
        </div>
        <x-ui.button :href="route('production-orders.show', $productionOrder)" variant="secondary">Kembali ke order</x-ui.button>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="space-y-5 xl:col-span-2">
            <article class="app-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-navy-900">Output batch</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-4">
                    <div><dt class="text-xs text-slate-500">Rencana</dt><dd class="mt-1 text-lg font-bold text-navy-900">{{ number_format($batch->planned_quantity) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Aktual</dt><dd class="mt-1 text-lg font-bold text-navy-900">{{ number_format($batch->actual_quantity) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Baik</dt><dd class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($batch->good_quantity) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Reject</dt><dd class="mt-1 text-lg font-bold text-rose-700">{{ number_format($batch->rejected_quantity) }}</dd></div>
                </dl>
                @if ($batch->notes)
                    <p class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $batch->notes }}</p>
                @endif
            </article>

            <article class="app-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-navy-900">Snapshot routing</h2>
                @if ($routingSnapshot)
                    <p class="mt-1 text-xs text-slate-500">{{ $routingSnapshot['name'] ?? 'Routing' }} · v{{ $routingSnapshot['version'] ?? '—' }}</p>
                    <ol class="mt-4 space-y-2">
                        @forelse ($routingSnapshot['operations'] ?? [] as $operation)
                            <li class="rounded-lg bg-slate-50 p-3 text-xs">
                                <span class="font-bold">{{ $operation['sequence'] ?? '—' }}. {{ $operation['name'] ?? 'Operasi' }}</span>
                                <span class="mt-1 block text-slate-500">Setup {{ $operation['setup_time_minutes'] ?? 0 }} menit · {{ $operation['estimated_energy_kwh'] ?? 0 }} kWh estimasi</span>
                            </li>
                        @empty
                            <li class="text-xs text-slate-500">Snapshot routing tidak memiliki operasi.</li>
                        @endforelse
                    </ol>
                @else
                    <p class="mt-3 text-sm text-slate-500">Routing tidak ditentukan pada snapshot batch.</p>
                @endif
            </article>

            <article class="app-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-navy-900">Energi dan costing batch</h2>
                <p class="mt-1 text-xs text-slate-500">Biaya aktual mencakup energi yang secara eksplisit ditautkan ke batch serta input labor dan overhead.</p>
                <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Energi routing estimasi</dt><dd class="mt-1 text-sm font-bold">{{ number_format($plannedEnergyKwh,3,',','.') }} kWh</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Energi aktual tertaut</dt><dd class="mt-1 text-sm font-bold">{{ number_format($actualEnergyKwh,3,',','.') }} kWh · Rp {{ number_format($actualEnergyCost,0,',','.') }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Labor aktual</dt><dd class="mt-1 text-sm font-bold">Rp {{ number_format($actualLaborCost,0,',','.') }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Overhead aktual</dt><dd class="mt-1 text-sm font-bold">Rp {{ number_format($actualOverheadCost,0,',','.') }}</dd></div>
                    <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 sm:col-span-2"><dt class="text-xs text-brand-800">Biaya aktual batch tercatat</dt><dd class="mt-1 text-base font-extrabold text-navy-900">Rp {{ number_format($actualCostTotal,0,',','.') }}</dd></div>
                </dl>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <h3 class="text-sm font-bold text-navy-900">Catatan energi</h3>
                    @forelse ($batch->energyRecords as $energy)
                        <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 py-2 text-xs">
                            <span>{{ $energy->recorded_at->format('d M Y H:i') }} · {{ $energy->source }} · {{ $energy->machine?->name ?? 'Mesin tidak ditautkan' }}@if($energy->routingOperation) · {{ $energy->routingOperation->name }}@endif</span>
                            <span class="font-semibold">{{ number_format((float)$energy->kwh,3,',','.') }} kWh · Rp {{ number_format((float)$energy->cost,0,',','.') }}</span>
                        </div>
                    @empty
                        <p class="mt-2 text-xs text-slate-500">Belum ada pembacaan energi yang ditautkan langsung ke batch ini. Data order-level tidak otomatis dialokasikan.</p>
                    @endforelse
                </div>

                @if (in_array($batch->status, ['in_progress','completed'], true))
                    <form method="POST" action="{{ route('production-orders.batches.costs.store', [$productionOrder,$batch]) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                        @csrf
                        <label class="text-xs font-semibold text-slate-700">Kategori
                            <select name="category" required class="app-input mt-1"><option value="labor">Labor aktual</option><option value="overhead">Overhead aktual</option></select>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Jumlah (Rp)
                            <input name="amount" type="number" min="0.01" step="any" required class="app-input mt-1">
                            <x-ui.form-hint>Nilai aktual yang ditambahkan ke costing batch; tidak mengubah biaya master produk.</x-ui.form-hint>
                        </label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Deskripsi biaya
                            <input name="description" maxlength="180" required class="app-input mt-1" placeholder="Catatan sumber biaya">
                            <x-ui.form-hint>Wajib diisi untuk menjelaskan dasar atau sumber biaya pada audit.</x-ui.form-hint>
                        </label>
                        <x-ui.button type="submit" class="sm:col-span-2">Catat biaya aktual</x-ui.button>
                    </form>
                @endif
            </article>

            <article class="app-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-navy-900">Catatan OEE tertaut</h2>
                @forelse ($batch->oeeRecords as $oee)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 py-2 text-xs">
                        <span>{{ $oee->recorded_on->format('d M Y') }} · {{ $oee->machine?->name ?? 'Mesin tidak ditautkan' }}@if($oee->routingOperation) · {{ $oee->routingOperation->name }}@endif</span>
                        <span class="font-bold">OEE {{ number_format((float)$oee->oee*100,1,',','.') }}% · A {{ number_format((float)$oee->availability*100,1,',','.') }}% · P {{ number_format((float)$oee->performance*100,1,',','.') }}% · Q {{ number_format((float)$oee->quality*100,1,',','.') }}%</span>
                    </div>
                @empty
                    <p class="mt-2 text-xs text-slate-500">Belum ada catatan OEE tertaut. Catatan lama tidak dialokasikan otomatis ke batch.</p>
                @endforelse
            </article>

            <article class="app-card p-5 sm:p-6">
                <h2 class="text-sm font-bold text-navy-900">Riwayat biaya manual</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($batch->costEntries as $entry)
                        <div class="py-2 text-xs">
                            <p class="font-semibold">{{ str($entry->category)->title() }} · Rp {{ number_format((float)$entry->amount,0,',','.') }}</p>
                            <p class="mt-0.5 text-slate-500">{{ $entry->description }} · {{ $entry->created_at->format('d M Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="py-3 text-xs text-slate-500">Belum ada input biaya labor/overhead.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <aside class="space-y-5">
            @if ($errors->any())
                <x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert>
            @endif
            @if ($batch->status === 'planned')
                <section class="app-card p-5 sm:p-6">
                    <h2 class="text-sm font-bold text-navy-900">Mulai batch</h2>
                    <p class="mt-1 text-xs text-slate-500">Snapshot routing aktif dengan operasi harus tersedia sebelum batch dimulai.</p>
                    <form method="POST" action="{{ route('production-orders.batches.start', [$productionOrder, $batch]) }}" class="mt-4">@csrf @method('PATCH')<x-ui.button type="submit">Mulai produksi</x-ui.button></form>
                    <p class="mt-3 text-xs text-rose-800">Pembatalan hanya tersedia sebelum batch dimulai; kuantitas batch batal tidak dihitung sebagai rencana aktif.</p>
                    <form method="POST" action="{{ route('production-orders.batches.cancel', [$productionOrder, $batch]) }}" class="mt-2">@csrf @method('PATCH')<button class="text-xs font-semibold text-rose-700" type="submit">Batalkan batch</button></form>
                </section>
            @elseif ($batch->status === 'in_progress')
                <section class="app-card p-5 sm:p-6">
                    <h2 class="text-sm font-bold text-navy-900">Selesaikan dan catat output</h2>
                    <form method="POST" action="{{ route('production-orders.batches.complete', [$productionOrder, $batch]) }}" class="mt-4 space-y-3">
                        @csrf @method('PATCH')
                        <p class="text-xs leading-5 text-slate-500">Penyelesaian menyimpan output batch dan memperbarui total order.</p>
                        <label for="actual_quantity" class="block text-xs font-semibold text-slate-700">Output aktual</label>
                        <input id="actual_quantity" name="actual_quantity" type="number" min="0" max="{{ $batch->planned_quantity }}" value="{{ old('actual_quantity', $batch->actual_quantity) }}" required class="app-input">
                        <x-ui.form-hint>Jumlah seluruh unit yang dihasilkan, termasuk unit reject. Maksimal {{ number_format($batch->planned_quantity) }} unit.</x-ui.form-hint>
                        @error('actual_quantity')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                        <label for="good_quantity" class="block text-xs font-semibold text-slate-700">Output baik</label>
                        <input id="good_quantity" name="good_quantity" type="number" min="0" max="{{ $batch->planned_quantity }}" value="{{ old('good_quantity', $batch->good_quantity) }}" required class="app-input">
                        <x-ui.form-hint>Jumlah unit yang memenuhi kriteria kualitas; tidak boleh melebihi output aktual.</x-ui.form-hint>
                        @error('good_quantity')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                        <label for="batch-completion-notes" class="block text-xs font-semibold text-slate-700">Catatan</label>
                        <textarea id="batch-completion-notes" name="notes" rows="3" class="app-input">{{ old('notes', $batch->notes) }}</textarea>
                        <x-ui.button type="submit">Catat penyelesaian</x-ui.button>
                    </form>
                </section>
            @endif

            <section class="app-card p-5 sm:p-6">
                <h2 class="text-sm font-bold text-navy-900">Riwayat batch</h2>
                <ol class="mt-3 space-y-3">
                    @forelse ($batch->events as $event)
                        <li class="border-l-2 border-brand-200 pl-3">
                            <p class="text-xs font-semibold text-navy-900">{{ str($event->event_type)->replace('_', ' ')->title() }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d M Y H:i') }}</p>
                        </li>
                    @empty
                        <li class="text-xs text-slate-500">Belum ada perubahan status.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
@endsection
