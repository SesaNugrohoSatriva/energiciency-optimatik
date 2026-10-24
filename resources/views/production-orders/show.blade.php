@extends('layouts.app')

@section('content')
    @php
        $routingSnapshot = $record->routing_snapshot;
    @endphp

    @if (empty($routingSnapshot['operations']))
        <x-ui.next-action class="mb-5" tone="warning">
            <p>Snapshot routing belum memiliki operasi. Pilih routing aktif yang berisi operasi sebelum memulai batch.</p>
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('production-orders.edit', $record) }}">Periksa routing order</a>
        </x-ui.next-action>
    @elseif ($record->status === 'Completed')
        <x-ui.next-action class="mb-5" tone="success">
            <p>Order ditandai selesai. Periksa output aktual dan batch yang tercatat untuk memastikan hasil produksi; gunakan riwayat batch untuk penelusuran.</p>
        </x-ui.next-action>
    @elseif ($record->batches->contains(fn ($batch) => $batch->status === 'in_progress'))
        <x-ui.next-action class="mb-5">
            <p>Batch sedang berjalan. Catat output aktual dan output baik saat pekerjaan selesai.</p>
            @php($activeBatch = $record->batches->first(fn ($batch) => $batch->status === 'in_progress'))
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('production-orders.batches.show', [$record, $activeBatch]) }}">Buka batch berjalan</a>
        </x-ui.next-action>
    @elseif ($record->batches->contains(fn ($batch) => $batch->status === 'planned'))
        <x-ui.next-action class="mb-5">
            <p>Batch masih direncanakan. Periksa routing snapshot, lalu mulai batch dari halaman detailnya.</p>
            @php($plannedBatch = $record->batches->first(fn ($batch) => $batch->status === 'planned'))
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('production-orders.batches.show', [$record, $plannedBatch]) }}">Periksa batch yang direncanakan</a>
        </x-ui.next-action>
    @elseif ($remaining > 0)
        <x-ui.next-action class="mb-5">
            <p>Struktur routing order sudah tersedia. Rencanakan batch hingga jumlahnya tidak melebihi sisa {{ number_format($remaining) }} unit; setiap batch akan menyalin snapshot routing ini.</p>
            <a class="mt-2 inline-flex font-semibold underline" href="#create-batch">Buat batch berikutnya</a>
        </x-ui.next-action>
    @else
        <x-ui.next-action class="mb-5">
            <p>Jumlah batch yang direncanakan telah mencapai batas kuantitas order. Periksa progres dan hasil pada daftar batch.</p>
        </x-ui.next-action>
    @endif

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Status order" :value="$record->status" detail="Progres mengikuti workflow batch" icon="S" />
        <x-ui.kpi-card label="Kuantitas rencana" :value="number_format($record->planned_quantity)" detail="Unit sesuai order produksi" icon="P" />
        <x-ui.kpi-card label="Output aktual" :value="number_format($record->actual_quantity)" detail="Agregat batch selesai" icon="A" tone="blue" />
        <x-ui.kpi-card label="Output baik" :value="number_format($record->good_quantity)" detail="Agregat batch selesai" icon="Q" tone="green" />
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.status-badge :status="$record->status" context="production-order" />
            <span class="text-xs text-slate-500">{{ $record->product?->sku }} · {{ $record->product?->name }}</span>
        </div>
        <x-ui.button :href="route('production-orders.edit', $record)">Edit order</x-ui.button>
    </div>

    @if ($errors->has('production_order'))
        <x-ui.alert type="error" class="mb-4">{{ $errors->first('production_order') }}</x-ui.alert>
    @endif
    @if ($errors->has('planned_quantity'))
        <x-ui.alert type="error" class="mb-4">{{ $errors->first('planned_quantity') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="app-card overflow-hidden xl:col-span-2">
            <div class="border-b border-slate-100 p-5 sm:px-6">
                <h2 class="text-base font-bold text-navy-900">Production batches</h2>
                <p class="mt-1 text-xs text-slate-500">Setiap batch menyimpan salinan routing saat dibuat. Routing aktif dengan operasi wajib sebelum produksi dimulai.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($record->batches as $batch)
                    <a href="{{ route('production-orders.batches.show', [$record, $batch]) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 transition hover:bg-slate-50 sm:px-6">
                        <div>
                            <p class="text-sm font-bold text-brand-700">{{ $batch->batch_number }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ number_format($batch->planned_quantity) }} planned · {{ number_format($batch->actual_quantity) }} actual · {{ $batch->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <x-ui.status-badge :status="$batch->status" context="production-batch" />
                    </a>
                @empty
                    <div class="p-5"><x-ui.empty-state title="Belum ada batch" description="Buat batch untuk memulai produksi yang dapat ditelusuri." /></div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-5">
            <section class="app-card p-5 sm:p-6">
                <h2 class="text-sm font-bold text-navy-900">Snapshot routing order</h2>
                @if ($routingSnapshot)
                    <div class="mt-3 rounded-lg bg-slate-50 p-3">
                        <p class="text-xs font-bold text-navy-900">{{ $routingSnapshot['name'] ?? 'Routing' }} · v{{ $routingSnapshot['version'] ?? '—' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ count($routingSnapshot['operations'] ?? []) }} operasi</p>
                    </div>
                @else
                    <p class="mt-3 text-xs text-amber-800">Routing belum ditetapkan pada snapshot order. Pilih routing aktif sebelum batch dapat dimulai.</p>
                @endif
            </section>

            @if ($remaining > 0 && $record->status !== 'Completed')
                <section id="create-batch" class="app-card p-5 sm:p-6">
                    <h2 class="text-sm font-bold text-navy-900">Buat batch</h2>
                    <p class="mt-1 text-xs text-slate-500">Jumlah unit dalam batch baru. Batas maksimum sisa order adalah {{ number_format($remaining) }} unit; tindakan ini membuat snapshot routing.</p>
                    <form method="POST" action="{{ route('production-orders.batches.store', $record) }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="planned_quantity" class="block text-xs font-semibold text-slate-700">Kuantitas rencana</label>
                        <input id="planned_quantity" name="planned_quantity" type="number" min="1" max="{{ $remaining }}" value="{{ old('planned_quantity', $remaining) }}" required class="app-input">
                        <x-ui.form-hint>Jumlah output yang direncanakan untuk batch ini. Total batch aktif tidak dapat melebihi kuantitas order.</x-ui.form-hint>
                        @error('planned_quantity')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                        <label for="batch-notes" class="block text-xs font-semibold text-slate-700">Catatan (opsional)</label>
                        <textarea id="batch-notes" name="notes" rows="2" class="app-input">{{ old('notes') }}</textarea>
                        <x-ui.button type="submit">Buat batch</x-ui.button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
@endsection
