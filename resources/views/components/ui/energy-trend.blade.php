@props(['data' => [], 'title' => 'Tren konsumsi energi'])

@php
    $values = array_column($data, 'total_kwh');
    $maxValue = max($values ?: [0]);
    $points = collect($data)->values()->map(function ($point, $index) use ($data, $maxValue) {
        $x = count($data) < 2 ? 360 : 36 + ($index * 648 / (count($data) - 1));
        $y = 190 - ($maxValue > 0 ? ($point['total_kwh'] / $maxValue) * 155 : 0);

        return ['x' => round($x, 2), 'y' => round($y, 2), ...$point];
    });
@endphp

<section {{ $attributes->merge(['class' => 'app-card p-5 sm:p-6']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-bold text-navy-900">{{ $title }}</h2>
            <p class="mt-1 text-xs text-slate-500">Jumlah kWh yang tersimpan per bucket waktu. Bucket tanpa record tidak dibuat; grafik menampilkan maksimal 180 bucket terbaru.</p>
        </div>
        <span class="inline-flex items-center gap-2 text-xs font-semibold text-brand-800">
            <span class="h-2 w-2 rounded-full bg-brand-600" aria-hidden="true"></span>
            Energi tercatat (kWh)
        </span>
    </div>
    @if (count($points))
        <div class="mt-5 overflow-x-auto">
            <svg viewBox="0 0 720 230" role="img" aria-label="{{ $title }} berdasarkan waktu" class="h-56 min-w-[560px] w-full">
                <line x1="36" y1="35" x2="684" y2="35" stroke="#e2e8f0" />
                <line x1="36" y1="112" x2="684" y2="112" stroke="#e2e8f0" />
                <line x1="36" y1="190" x2="684" y2="190" stroke="#cbd5e1" />
                <text x="0" y="39" fill="#64748b" font-size="10">{{ number_format($maxValue, 1, ',', '.') }}</text>
                <text x="0" y="194" fill="#64748b" font-size="10">0</text>
                @if ($points->count() > 1)
                    <polyline
                        fill="none"
                        stroke="#2563eb"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        points="{{ $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"
                    />
                @endif
                @foreach ($points as $point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="#fff" stroke="#2563eb" stroke-width="2">
                        <title>{{ $point['bucket'] }} · {{ number_format($point['total_kwh'], 2, ',', '.') }} kWh · {{ $point['record_count'] }} record · Rp {{ number_format($point['total_cost'], 0, ',', '.') }}</title>
                    </circle>
                @endforeach
            </svg>
        </div>
        <div class="flex justify-between gap-4 text-xs text-slate-500">
            <span>{{ $points->first()['bucket'] }}</span>
            @if ($points->count() > 2)
                <span>{{ $points[(int) floor(($points->count() - 1) / 2)]['bucket'] }}</span>
            @endif
            <span>{{ $points->last()['bucket'] }}</span>
        </div>
    @else
        <x-ui.empty-state class="mt-3" title="Belum ada pembacaan pada periode ini" description="Ubah rentang tanggal atau filter sumber untuk melihat data yang tersimpan." />
    @endif
</section>
