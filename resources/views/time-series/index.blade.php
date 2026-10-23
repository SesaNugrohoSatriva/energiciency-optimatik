@extends('layouts.app')

@section('content')

{{-- ─── Filter Bar ─────────────────────────────────────────────────────── --}}
<div class="app-card mb-5 p-4">
    <div class="flex flex-wrap items-end gap-3">

        {{-- Interval --}}
        <label class="flex flex-col gap-1">
            <span class="app-label">Resolusi</span>
            <select id="ts-interval" class="app-input">
                <option value="minute">Per Menit</option>
                <option value="day" selected>Per Hari</option>
                <option value="month">Per Bulan</option>
            </select>
        </label>

        {{-- Tanggal (menit / hari) --}}
        <label id="wrap-date" class="flex flex-col gap-1">
            <span class="app-label">Tanggal</span>
            <input id="ts-date" class="app-input" type="date" value="{{ now()->toDateString() }}">
        </label>

        {{-- Bulan / Tahun (mode month) --}}
        <label id="wrap-month" class="hidden flex-col gap-1">
            <span class="app-label">Bulan</span>
            <input id="ts-month" class="app-input" type="month" value="{{ now()->format('Y-m') }}">
        </label>

        {{-- Device ID --}}
        <label class="flex flex-col gap-1">
            <span class="app-label">Device ID <span class="font-normal text-slate-400">(opsional)</span></span>
            <input id="ts-device" class="app-input" type="text" placeholder="Semua perangkat" list="device-list">
            <datalist id="device-list">
                @foreach($devices as $dev)
                    <option value="{{ $dev->device_id }}">{{ $dev->device_id }}{{ $dev->machine_code ? ' · '.$dev->machine_code : '' }}</option>
                @endforeach
            </datalist>
        </label>

        <button id="ts-load" class="app-button" type="button">Tampilkan</button>
    </div>
</div>

{{-- ─── KPI Cards ───────────────────────────────────────────────────────── --}}
<div class="mb-5 grid gap-3 sm:grid-cols-2">
    <div class="app-card flex items-center gap-4 p-5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-sm">A</div>
        <div class="min-w-0">
            <p class="text-xs text-slate-500">Arus rata-rata</p>
            <p id="kpi-current-val" class="mt-0.5 text-2xl font-extrabold tracking-tight text-navy-900">—</p>
            <p class="text-xs text-slate-400">Rata-rata current_a pada periode</p>
        </div>
    </div>
    <div class="app-card flex items-center gap-4 p-5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 font-bold text-sm">W</div>
        <div class="min-w-0">
            <p class="text-xs text-slate-500">Daya rata-rata</p>
            <p id="kpi-power-val" class="mt-0.5 text-2xl font-extrabold tracking-tight text-navy-900">—</p>
            <p class="text-xs text-slate-400">Rata-rata power_w pada periode</p>
        </div>
    </div>
</div>

{{-- ─── Charts ──────────────────────────────────────────────────────────── --}}
<div class="mb-5 grid gap-4 lg:grid-cols-2">

    {{-- Chart Arus --}}
    <section class="app-card p-5">
        <div class="mb-4 flex items-start justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-navy-900">Arus (A)</h2>
                <p class="mt-0.5 text-xs text-slate-500">Nilai rata-rata <code>current_a</code> dari payload IoT per bucket waktu</p>
            </div>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-800">
                <span class="h-2 w-2 rounded-full bg-brand-600"></span>A
            </span>
        </div>
        <div class="relative h-56 overflow-x-auto">
            <div id="chart-current-empty" class="absolute inset-0 flex items-center justify-center">
                <p class="text-sm text-slate-400">Tekan <strong>Tampilkan</strong> untuk memuat data.</p>
            </div>
            <svg id="chart-current-svg" class="hidden h-full min-w-[480px] w-full" viewBox="0 0 720 200" role="img" aria-label="Grafik arus"></svg>
        </div>
        <div id="chart-current-labels" class="mt-1 flex justify-between text-xs text-slate-400"></div>
    </section>

    {{-- Chart Daya --}}
    <section class="app-card p-5">
        <div class="mb-4 flex items-start justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-navy-900">Daya (W)</h2>
                <p class="mt-0.5 text-xs text-slate-500">Nilai rata-rata <code>power_w</code> dari payload IoT per bucket waktu</p>
            </div>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>W
            </span>
        </div>
        <div class="relative h-56 overflow-x-auto">
            <div id="chart-power-empty" class="absolute inset-0 flex items-center justify-center">
                <p class="text-sm text-slate-400">Tekan <strong>Tampilkan</strong> untuk memuat data.</p>
            </div>
            <svg id="chart-power-svg" class="hidden h-full min-w-[480px] w-full" viewBox="0 0 720 200" role="img" aria-label="Grafik daya"></svg>
        </div>
        <div id="chart-power-labels" class="mt-1 flex justify-between text-xs text-slate-400"></div>
    </section>
</div>

{{-- ─── Tabel Data ──────────────────────────────────────────────────────── --}}
<section class="app-card">
    <div class="flex items-center justify-between px-5 py-4">
        <h2 class="text-base font-bold text-navy-900">Tabel Data</h2>
        <button id="ts-export" type="button" class="app-button-secondary text-xs" disabled>Ekspor CSV</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-right">Arus (A)</th>
                    <th class="px-4 py-3 text-right">Daya (W)</th>
                </tr>
            </thead>
            <tbody id="ts-tbody" class="divide-y divide-slate-100 text-slate-700">
                <tr>
                    <td colspan="3" class="py-10 text-center text-sm text-slate-400">
                        Belum ada data. Pilih filter lalu tekan Tampilkan.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-5 py-3">
        <p id="ts-count" class="text-xs text-slate-400">—</p>
    </div>
</section>

{{-- ─── Script ──────────────────────────────────────────────────────────── --}}
<script>
(function () {
    'use strict';

    /* ── DOM refs ─────────────────────────────────────────────────────── */
    const elInterval = document.getElementById('ts-interval');
    const elDate     = document.getElementById('ts-date');
    const elMonth    = document.getElementById('ts-month');
    const elDevice   = document.getElementById('ts-device');
    const elLoad     = document.getElementById('ts-load');
    const elExport   = document.getElementById('ts-export');
    const elTbody    = document.getElementById('ts-tbody');
    const elCount    = document.getElementById('ts-count');
    const wrapDate   = document.getElementById('wrap-date');
    const wrapMonth  = document.getElementById('wrap-month');
    const kpiCurr    = document.getElementById('kpi-current-val');
    const kpiPow     = document.getElementById('kpi-power-val');

    /* ── Toggle date / month picker ───────────────────────────────────── */
    elInterval.addEventListener('change', () => {
        const isMonth = elInterval.value === 'month';
        wrapDate.classList.toggle('hidden', isMonth);
        wrapMonth.classList.toggle('hidden', !isMonth);
        wrapDate.classList.toggle('flex', !isMonth);
        wrapMonth.classList.toggle('flex', isMonth);
    });

    /* ── SVG helpers ──────────────────────────────────────────────────── */
    const SVG_NS = 'http://www.w3.org/2000/svg';

    function el(tag, attrs) {
        const e = document.createElementNS(SVG_NS, tag);
        Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v));
        return e;
    }

    function renderChart(svgId, emptyId, labelsId, points, strokeColor, fillColor) {
        const svg    = document.getElementById(svgId);
        const empty  = document.getElementById(emptyId);
        const labels = document.getElementById(labelsId);

        svg.innerHTML = '';

        if (!points.length) {
            svg.classList.add('hidden');
            empty.classList.remove('hidden');
            empty.querySelector('p').textContent = 'Tidak ada data pada periode ini.';
            labels.innerHTML = '';
            return;
        }

        svg.classList.remove('hidden');
        empty.classList.add('hidden');

        const W = 720, H = 200;
        const PAD = { top: 20, right: 16, bottom: 8, left: 56 };
        const iW  = W - PAD.left - PAD.right;
        const iH  = H - PAD.top  - PAD.bottom;

        const vals   = points.map(p => p.value);
        const maxVal = Math.max(...vals, 0.001);
        const minVal = Math.min(...vals, 0);
        const range  = maxVal - minVal || 1;

        const cx = i => PAD.left + (points.length < 2 ? iW / 2 : (i / (points.length - 1)) * iW);
        const cy = v => PAD.top  + iH - ((v - minVal) / range) * iH;

        /* Grid & Y labels */
        [0, 0.25, 0.5, 0.75, 1].forEach(f => {
            const y   = PAD.top + iH * (1 - f);
            const val = minVal + range * f;
            svg.appendChild(el('line', { x1: PAD.left, x2: W - PAD.right, y1: y, y2: y,
                stroke: f === 0 ? '#cbd5e1' : '#e2e8f0', 'stroke-width': '1' }));
            const t = el('text', { x: PAD.left - 4, y: y + 4,
                'text-anchor': 'end', 'font-size': '10', fill: '#64748b' });
            t.textContent = val >= 1000 ? (val / 1000).toFixed(1) + 'k'
                          : val >= 100  ? val.toFixed(0)
                          : val >= 10   ? val.toFixed(1)
                          : val.toFixed(2);
            svg.appendChild(t);
        });

        if (points.length > 1) {
            const ptStr = points.map((p, i) => `${cx(i)},${cy(p.value)}`).join(' ');
            const bY    = PAD.top + iH;

            /* Area */
            const poly = el('polygon', { fill: fillColor,
                points: `${cx(0)},${bY} ${ptStr} ${cx(points.length-1)},${bY}` });
            svg.appendChild(poly);

            /* Line */
            svg.appendChild(el('polyline', { points: ptStr, fill: 'none',
                stroke: strokeColor, 'stroke-width': '2.5',
                'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));
        }

        /* Dots */
        const showDots = points.length <= 120;
        points.forEach((p, i) => {
            const c = el('circle', { cx: cx(i), cy: cy(p.value),
                r: showDots ? '4' : '0', fill: '#fff',
                stroke: strokeColor, 'stroke-width': '2' });
            const tt = document.createElementNS(SVG_NS, 'title');
            tt.textContent = `${p.period}  ·  ${p.value}`;
            c.appendChild(tt);
            svg.appendChild(c);
        });

        /* X labels */
        const picks = [0];
        if (points.length > 2) picks.push(Math.floor((points.length - 1) / 2));
        if (points.length > 1) picks.push(points.length - 1);
        labels.innerHTML = picks.map(i => `<span>${points[i].period}</span>`).join('');
    }

    /* ── Fetch & render ───────────────────────────────────────────────── */
    let lastData = [];

    async function load() {
        elLoad.disabled    = true;
        elLoad.textContent = 'Memuat…';

        const interval = elInterval.value;
        const device   = elDevice.value.trim();
        const date     = interval === 'month'
            ? (elMonth.value ? elMonth.value + '-01' : '')
            : (elDate.value || '');

        const params = new URLSearchParams({ interval });
        if (date)   params.set('date',      date);
        if (device) params.set('device_id', device);

        try {
            const res = await fetch(`/api/time-series?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest',
                           'Accept': 'application/json' },
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message ?? `HTTP ${res.status}`);
            }
            const json = await res.json();
            lastData = json.data ?? [];
        } catch (err) {
            elTbody.innerHTML = `<tr><td colspan="3"
                class="py-8 text-center text-sm text-red-500">
                Gagal memuat data: ${err.message}</td></tr>`;
            elCount.textContent = '—';
            elLoad.disabled    = false;
            elLoad.textContent = 'Tampilkan';
            return;
        }

        /* KPI */
        if (lastData.length) {
            const avgA = (lastData.reduce((s, r) => s + r.current_a, 0) / lastData.length).toFixed(3);
            const avgW = (lastData.reduce((s, r) => s + r.power_w,   0) / lastData.length).toFixed(1);
            kpiCurr.textContent = `${avgA} A`;
            kpiPow.textContent  = `${avgW} W`;
        } else {
            kpiCurr.textContent = '—';
            kpiPow.textContent  = '—';
        }

        /* Charts */
        renderChart(
            'chart-current-svg', 'chart-current-empty', 'chart-current-labels',
            lastData.map(r => ({ period: r.period, value: r.current_a })),
            '#2563eb', '#2563eb22'
        );
        renderChart(
            'chart-power-svg', 'chart-power-empty', 'chart-power-labels',
            lastData.map(r => ({ period: r.period, value: r.power_w })),
            '#d97706', '#d9770622'
        );

        /* Table */
        elTbody.innerHTML = lastData.length
            ? lastData.map(r => `
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600">${r.period}</td>
                    <td class="px-4 py-2.5 text-right tabular-nums text-navy-900">${r.current_a}</td>
                    <td class="px-4 py-2.5 text-right tabular-nums text-navy-900">${r.power_w}</td>
                </tr>`).join('')
            : `<tr><td colspan="3"
                class="py-10 text-center text-sm text-slate-400">
                Tidak ada data pada periode dan filter ini.</td></tr>`;

        elCount.textContent = `${lastData.length} bucket ditampilkan`;
        elExport.disabled   = lastData.length === 0;
        elLoad.disabled     = false;
        elLoad.textContent  = 'Tampilkan';
    }

    elLoad.addEventListener('click', load);

    /* ── Export CSV ───────────────────────────────────────────────────── */
    elExport.addEventListener('click', () => {
        if (!lastData.length) return;
        const rows = [
            ['Periode', 'Arus (A)', 'Daya (W)'],
            ...lastData.map(r => [r.period, r.current_a, r.power_w]),
        ];
        const csv = rows.map(r => r.join(',')).join('\n');
        const a   = Object.assign(document.createElement('a'), {
            href:     URL.createObjectURL(new Blob([csv], { type: 'text/csv' })),
            download: `time-series-${elInterval.value}-${Date.now()}.csv`,
        });
        a.click();
        URL.revokeObjectURL(a.href);
    });

})();
</script>
@endsection
