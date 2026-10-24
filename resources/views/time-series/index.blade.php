@extends('layouts.app')

@section('content')

@include('partials.live-monitor')

<section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-navy-900 px-5 py-5 text-white sm:px-7 sm:py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-blue-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 8L9 4l-3 8H2"/></svg>
                    Telemetri tersimpan
                </div>
                <h2 class="text-xl font-bold tracking-tight sm:text-2xl">Pantau performa kelistrikan mesin</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-blue-100/80">Telusuri tren arus dan daya berdasarkan periode pengukuran dan perangkat. Data ditampilkan dari telemetri IoT yang tersimpan.</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-100/70">Bucket terbaru</p>
                <p id="ts-latest-period" class="mt-1 text-sm font-semibold text-white">Belum dimuat</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-navy-900">Parameter pemantauan</h3>
                <p class="mt-1 text-xs text-slate-500">Pilih resolusi waktu dan perangkat, lalu muat data.</p>
            </div>
            <p id="ts-status" role="status" aria-live="polite" class="text-right text-xs text-slate-500">Menunggu filter untuk memuat data.</p>
        </div>
        <div class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1.4fr_auto]">
            <label class="min-w-0">
                <span class="app-label">Resolusi</span>
                <select id="ts-interval" class="app-input">
                    <option value="minute">Per Menit</option>
                    <option value="day" selected>Per Hari</option>
                    <option value="month">Per Bulan</option>
                </select>
            </label>
            <label id="wrap-date" class="flex min-w-0 flex-col">
                <span class="app-label">Tanggal</span>
                <input id="ts-date" class="app-input" type="date" value="{{ now()->toDateString() }}">
            </label>
            <label id="wrap-month" class="hidden min-w-0 flex-col">
                <span class="app-label">Bulan</span>
                <input id="ts-month" class="app-input" type="month" value="{{ now()->format('Y-m') }}">
            </label>
            <label class="min-w-0">
                <span class="app-label">Device ID <span class="font-normal text-slate-400">(opsional)</span></span>
                <input id="ts-device" class="app-input" type="text" placeholder="Semua perangkat" list="device-list">
                <datalist id="device-list">
                    @foreach($devices as $dev)
                        <option value="{{ $dev->device_id }}">{{ $dev->device_id }}{{ $dev->machine_code ? ' · '.$dev->machine_code : '' }}</option>
                    @endforeach
                </datalist>
            </label>
            <button id="ts-load" class="app-button app-button-primary w-full sm:col-span-2 xl:col-span-1" type="button">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8.1 8.1 0 0 0-14.9-4M4 4v4h4M4 13a8.1 8.1 0 0 0 14.9 4M20 20v-4h-4"/></svg>
                <span id="ts-load-label">Tampilkan</span>
            </button>
        </div>
    </div>
</section>

<section aria-label="Ringkasan telemetri" class="mb-5 grid gap-3 sm:grid-cols-2">
    <article class="app-card flex items-center gap-4 p-4 sm:p-5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 8L9 4l-3 8H2"/></svg>
        </span>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-500">Arus rata-rata</p>
            <p id="kpi-current-val" class="mt-1 text-2xl font-extrabold tracking-tight text-navy-900">—</p>
            <p class="mt-1 text-xs text-slate-400">Nilai rata-rata current_a pada hasil filter</p>
        </div>
    </article>
    <article class="app-card flex items-center gap-4 p-4 sm:p-5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-3 8h7l-6 12 1-9H5l8-11Z"/></svg>
        </span>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-500">Daya rata-rata</p>
            <p id="kpi-power-val" class="mt-1 text-2xl font-extrabold tracking-tight text-navy-900">—</p>
            <p class="mt-1 text-xs text-slate-400">Nilai rata-rata power_w pada hasil filter</p>
        </div>
    </article>
</section>

<section class="app-card mb-5 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">
        <div>
            <h2 class="text-base font-bold text-navy-900">Tren arus dan daya</h2>
            <p class="mt-1 text-xs text-slate-500">Rata-rata nilai telemetry per bucket waktu.</p>
        </div>
        <button id="ts-export" type="button" class="app-button-secondary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold" disabled>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Ekspor CSV
        </button>
    </div>
    <div class="grid gap-4 p-4 sm:p-5">
        <article class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/60 p-3 sm:p-4">
            <div class="mb-3 flex items-start justify-between gap-2">
                <div><h3 class="text-sm font-bold text-navy-900">Arus listrik</h3><p class="mt-1 text-xs text-slate-500">current_a · Ampere (A)</p></div>
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold text-brand-800"><span class="h-2 w-2 rounded-full bg-brand-600"></span>Arus</span>
            </div>
            <div class="relative h-64 overflow-x-auto rounded-lg bg-white">
                <div id="chart-current-empty" class="absolute inset-0 flex items-center justify-center px-4 text-center"><p class="text-sm text-slate-400">Pilih filter lalu tekan Tampilkan untuk memuat data.</p></div>
                <svg id="chart-current-svg" class="hidden h-full min-w-[600px] w-full" viewBox="0 0 720 200" role="img" aria-label="Grafik arus listrik dalam ampere"></svg>
            </div>
            <div id="chart-current-labels" class="mt-2 flex justify-between gap-2 text-[11px] text-slate-500"></div>
        </article>
        <article class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/60 p-3 sm:p-4">
            <div class="mb-3 flex items-start justify-between gap-2">
                <div><h3 class="text-sm font-bold text-navy-900">Daya aktif</h3><p class="mt-1 text-xs text-slate-500">power_w · Watt (W)</p></div>
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Daya</span>
            </div>
            <div class="relative h-64 overflow-x-auto rounded-lg bg-white">
                <div id="chart-power-empty" class="absolute inset-0 flex items-center justify-center px-4 text-center"><p class="text-sm text-slate-400">Pilih filter lalu tekan Tampilkan untuk memuat data.</p></div>
                <svg id="chart-power-svg" class="hidden h-full min-w-[600px] w-full" viewBox="0 0 720 200" role="img" aria-label="Grafik daya aktif dalam watt"></svg>
            </div>
            <div id="chart-power-labels" class="mt-2 flex justify-between gap-2 text-[11px] text-slate-500"></div>
        </article>
    </div>
</section>

<section class="app-card overflow-hidden">
    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
        <h2 class="text-base font-bold text-navy-900">Rincian pengukuran</h2>
        <p class="mt-1 text-xs text-slate-500">Nilai rata-rata arus dan daya untuk setiap bucket pada periode terpilih.</p>
    </div>
    <div class="max-h-[60vh] overflow-auto overscroll-contain">
        <table class="w-full min-w-[520px] text-sm">
            <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3 text-left sm:px-5">Periode</th><th class="px-4 py-3 text-right sm:px-5">Arus (A)</th><th class="px-4 py-3 text-right sm:px-5">Daya (W)</th></tr>
            </thead>
            <tbody id="ts-tbody" class="divide-y divide-slate-100 text-slate-700">
                <tr><td colspan="3" class="py-10 text-center text-sm text-slate-400">Belum ada data. Pilih filter lalu tekan Tampilkan.</td></tr>
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-4 py-3 sm:px-5"><p id="ts-count" class="text-xs text-slate-500">—</p></div>
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
    const elLoadLabel = document.getElementById('ts-load-label');
    const elExport   = document.getElementById('ts-export');
    const elTbody    = document.getElementById('ts-tbody');
    const elCount    = document.getElementById('ts-count');
    const elStatus   = document.getElementById('ts-status');
    const elLatestPeriod = document.getElementById('ts-latest-period');
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

    function showChartMessage(svgId, emptyId, labelsId, message) {
        document.getElementById(svgId).classList.add('hidden');
        const empty = document.getElementById(emptyId);
        empty.classList.remove('hidden');
        empty.querySelector('p').textContent = message;
        document.getElementById(labelsId).innerHTML = '';
    }

    /* ── Fetch & render ───────────────────────────────────────────────── */
    let lastData = [];

    async function load() {
        elLoad.disabled    = true;
        elLoad.setAttribute('aria-busy', 'true');
        elLoadLabel.textContent = 'Memuat…';
        elStatus.textContent = 'Sedang memuat data dari telemetri tersimpan…';

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
            lastData = [];
            elTbody.innerHTML = '<tr><td colspan="3" class="py-8 text-center text-sm text-red-600"><span id="ts-load-error"></span></td></tr>';
            document.getElementById('ts-load-error').textContent = `Gagal memuat data: ${err.message}`;
            elCount.textContent = '—';
            elExport.disabled = true;
            kpiCurr.textContent = '—';
            kpiPow.textContent = '—';
            elLatestPeriod.textContent = 'Tidak tersedia';
            elStatus.textContent = 'Permintaan gagal. Periksa koneksi lalu coba lagi.';
            showChartMessage('chart-current-svg', 'chart-current-empty', 'chart-current-labels', 'Data gagal dimuat. Silakan coba lagi.');
            showChartMessage('chart-power-svg', 'chart-power-empty', 'chart-power-labels', 'Data gagal dimuat. Silakan coba lagi.');
            elLoad.disabled    = false;
            elLoad.removeAttribute('aria-busy');
            elLoadLabel.textContent = 'Tampilkan';
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
        elLatestPeriod.textContent = lastData.length ? lastData[lastData.length - 1].period : 'Tidak ada data';
        elStatus.textContent = lastData.length
            ? `${lastData.length} bucket dimuat · ${new Date().toLocaleString('id-ID')}`
            : 'Tidak ada data pada periode dan filter ini.';
        elLoad.disabled     = false;
        elLoad.removeAttribute('aria-busy');
        elLoadLabel.textContent = 'Tampilkan';
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
