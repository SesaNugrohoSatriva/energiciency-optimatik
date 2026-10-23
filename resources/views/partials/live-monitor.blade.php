{{-- Live Monitor Widget — polling tiap 5 detik --}}
<section aria-label="Live Monitor IoT" class="mt-6">
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span id="live-dot" class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
            <h2 class="text-sm font-semibold text-slate-700">Live Monitor</h2>
        </div>
        <span id="live-timestamp" class="text-xs text-slate-400">Menghubungkan...</span>
    </div>

    <div id="live-cards" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        {{-- Diisi oleh JavaScript --}}
        <div class="app-card col-span-full p-6 text-center text-sm text-slate-400">
            Memuat data perangkat...
        </div>
    </div>
</section>

<script>
(function () {
    const API_URL  = '{{ url("/api/iot/latest") }}';
    const dot      = document.getElementById('live-dot');
    const ts       = document.getElementById('live-timestamp');
    const cards    = document.getElementById('live-cards');

    function fmt(val, decimals = 1) {
        if (val === null || val === undefined) return '—';
        return parseFloat(val).toFixed(decimals);
    }

    // Tampilkan kWh apa adanya: tidak pakai toFixed agar angka kecil tidak dipotong
    function fmtKwh(val) {
        if (val === null || val === undefined) return '—';
        const n = parseFloat(val);
        if (isNaN(n)) return '—';
        // Gunakan toPrecision untuk angka sangat kecil, tapi tetap tampil angka asli
        if (n === 0) return '0';
        if (n < 0.00001) return n.toExponential(4);   // misal 1.2e-6
        if (n < 0.001)   return n.toPrecision(4);      // misal 0.00002340
        return n.toString();                            // angka normal, apa adanya
    }

    function renderCard(d) {
        return `
        <article class="app-card p-4">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">${d.device_id}</p>
                    <p class="text-sm font-semibold text-slate-700">${d.machine_name}</p>
                </div>
                <span class="text-xs text-slate-400">${d.recorded_at}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-center sm:grid-cols-3">
                <div class="rounded-lg bg-blue-50 px-2 py-2">
                    <p class="text-lg font-bold text-blue-700">${fmt(d.voltage_v, 1)}</p>
                    <p class="text-xs text-blue-500">Tegangan (V)</p>
                </div>
                <div class="rounded-lg bg-green-50 px-2 py-2">
                    <p class="text-lg font-bold text-green-700">${fmt(d.current_a, 3)}</p>
                    <p class="text-xs text-green-500">Arus (A)</p>
                </div>
                <div class="rounded-lg bg-orange-50 px-2 py-2">
                    <p class="text-lg font-bold text-orange-700">${fmt(d.power_w, 1)}</p>
                    <p class="text-xs text-orange-500">Daya (W)</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-2 py-2">
                    <p class="text-lg font-bold text-slate-700">${fmt(d.frequency_hz, 1)}</p>
                    <p class="text-xs text-slate-500">Frekuensi (Hz)</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-2 py-2">
                    <p class="text-lg font-bold text-slate-700">${fmt(d.power_factor, 3)}</p>
                    <p class="text-xs text-slate-500">Power Factor</p>
                </div>
                <div class="rounded-lg bg-purple-50 px-2 py-2">
                    <p class="text-lg font-bold text-purple-700">${fmtKwh(d.kwh)}</p>
                    <p class="text-xs text-purple-500">kWh</p>
                </div>
            </div>
        </article>`;
    }

    async function fetchLatest() {
        try {
            const res  = await fetch(API_URL);
            const json = await res.json();

            if (!json.ok || json.data.length === 0) {
                cards.innerHTML = `
                    <div class="app-card col-span-full p-6 text-center text-sm text-slate-400">
                        Belum ada data IoT masuk. Pastikan ESP32 sudah terhubung dan terdaftar.
                    </div>`;
                dot.className = 'h-2.5 w-2.5 rounded-full bg-gray-300';
                ts.textContent = 'Tidak ada perangkat aktif';
                return;
            }

            cards.innerHTML = json.data.map(renderCard).join('');
            dot.className   = 'h-2.5 w-2.5 rounded-full bg-green-500 animate-pulse';
            ts.textContent  = 'Update: ' + new Date().toLocaleTimeString('id-ID');

        } catch (e) {
            dot.className  = 'h-2.5 w-2.5 rounded-full bg-red-400';
            ts.textContent = 'Gagal terhubung ke server';
        }
    }

    // Fetch pertama langsung, lalu tiap 5 detik
    fetchLatest();
    setInterval(fetchLatest, 5000);
})();
</script>
