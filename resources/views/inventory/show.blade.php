@extends('layouts.app')

@section('content')
    @if ((float) $record->quantity <= (float) $record->threshold)
        <x-ui.next-action class="mb-5" tone="warning">
            <p>Saldo on-hand berada pada atau di bawah batas minimum. Tinjau pembelian atau penyesuaian stok; perubahan saldo yang disimpan akan menambah transaksi pada ledger.</p>
            <a class="mt-2 inline-flex font-semibold underline" href="{{ route('inventory.edit', $record) }}">Tinjau saldo dan batas minimum</a>
        </x-ui.next-action>
    @else
        <x-ui.next-action class="mb-5">
            <p>Stok bebas dihitung sebagai on-hand dikurangi reservasi aktif. Gunakan ledger untuk menelusuri perubahan saldo dan pemakaian batch.</p>
        </x-ui.next-action>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2"><span class="text-xs font-bold text-brand-700">{{ $record->sku }}</span><span class="text-xs text-slate-500">{{ $record->location ?? 'Lokasi belum ditentukan' }}</span></div>
        <x-ui.button :href="route('inventory.edit', $record)">Edit item</x-ui.button>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <x-ui.kpi-card label="On-hand" :value="number_format((float)$record->quantity,3,',','.').' '.$record->unit" detail="Saldo fisik tersimpan" icon="Q" />
        <x-ui.kpi-card label="Reservasi aktif" :value="number_format($reserved,3,',','.').' '.$record->unit" detail="Tidak mengurangi saldo on-hand" icon="R" tone="blue" />
        <x-ui.kpi-card label="Tersedia bebas (estimasi)" :value="number_format(max(0,(float)$record->quantity-$reserved),3,',','.').' '.$record->unit" detail="On-hand dikurangi reservasi aktif" icon="A" tone="green" />
    </div>

    <section class="app-card overflow-hidden">
        <div class="border-b border-slate-100 p-5 sm:px-6">
            <h2 class="text-base font-bold text-navy-900">Ledger inventory</h2>
            <p class="mt-1 text-xs text-slate-500">Pembukaan saldo, penyesuaian, reservasi, pelepasan, dan konsumsi tercatat append-only.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3 text-right">Kuantitas</th><th class="px-4 py-3 text-right">Biaya snapshot</th><th class="px-4 py-3">Batch / referensi</th><th class="px-4 py-3">User</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transactions as $transaction)
                        <tr><td class="px-4 py-3">{{ $transaction->created_at->format('d M Y H:i') }}</td><td class="px-4 py-3">{{ str($transaction->transaction_type)->replace('_',' ')->title() }}</td><td class="px-4 py-3 text-right">{{ number_format((float)$transaction->quantity,3,',','.') }} {{ $record->unit }}</td><td class="px-4 py-3 text-right">Rp {{ number_format((float)$transaction->unit_cost_snapshot,0,',','.') }}</td><td class="px-4 py-3">@if($transaction->batch)<a class="font-semibold text-brand-700" href="{{ route('production-orders.batches.show',[$transaction->batch->productionOrder,$transaction->batch]) }}">{{ $transaction->reference }}</a>@else{{ $transaction->reference ?? '—' }}@endif</td><td class="px-4 py-3">{{ $transaction->user?->name ?? 'System' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-0"><x-ui.empty-state title="Belum ada transaksi" description="Transaksi akan dicatat saat saldo dibuka, disesuaikan, direservasi, atau dikonsumsi." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $transactions->links() }}</div>
    </section>
@endsection
