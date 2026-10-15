@extends('layouts.app')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <p class="text-sm text-slate-500">Agregasi semua catatan energi sesuai tanggal pencatatan.</p>
        <x-ui.button href="{{ route('energy-records.index') }}" variant="secondary">Buka data energi</x-ui.button>
    </div>

    <x-ui.table>
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th scope="col" class="px-4 py-3.5">Periode</th>
                <th scope="col" class="px-4 py-3.5 text-right">Energi (kWh)</th>
                <th scope="col" class="px-4 py-3.5 text-right">Biaya energi (Rp)</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($monthly as $row)
                <tr>
                    <th scope="row" class="px-4 py-4 text-left font-semibold text-navy-900">{{ \Illuminate\Support\Carbon::parse($row->period)->translatedFormat('F Y') }}</th>
                    <td class="px-4 py-4 text-right tabular-nums text-slate-600">{{ number_format($row->kwh, 2, ',', '.') }}</td>
                    <td class="px-4 py-4 text-right font-semibold tabular-nums text-navy-900">{{ number_format($row->cost, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="p-0"><x-ui.empty-state title="Belum ada laporan energi" description="Periode laporan akan muncul setelah data energi tersimpan." /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
@endsection
