@props(['status', 'context' => null, 'description' => null])

@php
    $normalized = strtolower((string) $status);
    $definitions = [
        'production-order' => [
            'planned' => 'Order direncanakan, tetapi batch belum mulai dikerjakan.',
            'in progress' => 'Setidaknya satu batch sedang dikerjakan.',
            'completed' => 'Order ditandai selesai. Periksa output aktual dan batch yang tercatat untuk memastikan hasilnya.',
            'delayed' => 'Jadwal order terlambat dibanding rencana yang tercatat.',
        ],
        'production-batch' => [
            'planned' => 'Batch belum dimulai. Snapshot BOM, routing, pemetaan inventory, dan reservasi material harus siap lebih dahulu.',
            'in_progress' => 'Batch sedang diproses. Catat konsumsi material dan output aktual sebelum menyelesaikannya.',
            'completed' => 'Batch selesai dan outputnya telah diperhitungkan pada progres order.',
            'cancelled' => 'Batch dibatalkan sebelum produksi dimulai; kuantitasnya tidak dihitung sebagai rencana batch aktif.',
        ],
        'structure' => [
            'draft' => 'Versi ini masih disiapkan dan belum menjadi struktur aktif untuk order baru.',
            'active' => 'Versi ini digunakan sebagai struktur aktif saat snapshot order dibuat.',
            'archived' => 'Versi ini disimpan untuk riwayat dan tidak lagi menjadi versi aktif.',
        ],
        'machine' => [
            'normal' => 'Mesin ditandai beroperasi normal pada status yang tercatat.',
            'monitor' => 'Mesin perlu dipantau; status ini bukan indikator koneksi IoT.',
            'down' => 'Mesin ditandai tidak tersedia atau mengalami gangguan.',
        ],
        'maintenance' => [
            'open' => 'Permintaan pemeliharaan tercatat dan belum dijadwalkan atau diselesaikan.',
            'monitor' => 'Kondisi dicatat untuk dipantau; tindak lanjut ditentukan oleh tim operasional.',
            'planned' => 'Pekerjaan pemeliharaan telah direncanakan.',
            'in progress' => 'Pekerjaan pemeliharaan sedang berlangsung.',
            'completed' => 'Pekerjaan pemeliharaan ditandai selesai.',
        ],
        'source' => [
            'simulation' => 'Data simulasi untuk pengembangan/pengujian, bukan hasil pengukuran sensor aktual.',
            'manual' => 'Data dimasukkan secara manual oleh pengguna.',
            'iot' => 'Data diberi label berasal dari integrasi IoT; badge ini tidak menunjukkan status online perangkat.',
        ],
        'priority' => [
            'low' => 'Prioritas rendah.',
            'medium' => 'Prioritas sedang.',
            'high' => 'Prioritas tinggi.',
            'critical' => 'Prioritas kritis dan perlu ditinjau segera.',
        ],
        'boolean' => [
            'active' => 'Produk dapat dipilih untuk proses baru.',
            'inactive' => 'Produk tidak aktif untuk penggunaan baru.',
        ],
    ];
    $description ??= $definitions[$context ?? ''][$normalized] ?? match ($normalized) {
        'normal' => 'Kondisi berstatus normal berdasarkan catatan yang tersedia.',
        'monitor' => 'Perlu dipantau berdasarkan status yang tercatat.',
        'down' => 'Ditandai tidak tersedia berdasarkan status yang tercatat.',
        'draft' => 'Masih disiapkan dan belum diaktifkan.',
        'active' => 'Sedang berlaku sebagai versi aktif.',
        'archived' => 'Disimpan untuk riwayat dan bukan versi aktif.',
        'planned' => 'Sudah direncanakan dan menunggu langkah proses berikutnya.',
        'in progress', 'in_progress' => 'Sedang diproses.',
        'completed' => 'Proses ditandai selesai.',
        'cancelled' => 'Proses dibatalkan.',
        'reserved' => 'Dialokasikan dan tidak tersedia bebas untuk kebutuhan lain.',
        'consumed' => 'Pemakaian aktual telah dicatat.',
        'open' => 'Masih terbuka dan belum ditandai selesai.',
        'quality check' => 'Operasi ini memiliki persyaratan pemeriksaan kualitas.',
        'optional' => 'Operasi ditandai opsional pada routing.',
        'wajib' => 'Operasi ini bukan operasi opsional pada routing.',
        default => null,
    };
    $displayLabel = $context === 'source'
        ? match ($normalized) {
            'simulation' => 'SIMULASI',
            'iot' => 'IoT',
            default => str((string) $status)->replace('_', ' ')->title(),
        }
        : str((string) $status)->replace('_', ' ')->title();
    $classes = match (true) {
        in_array($normalized, ['normal', 'completed', 'active', 'consumed']) => 'bg-emerald-50 text-emerald-800 ring-emerald-600/15',
        in_array($normalized, ['planned', 'medium', 'in progress', 'in_progress', 'iot', 'simulation', 'draft', 'reserved']) => 'bg-brand-50 text-brand-800 ring-brand-600/15',
        in_array($normalized, ['monitor', 'delayed', 'high', 'open']) => 'bg-amber-50 text-amber-900 ring-amber-600/15',
        in_array($normalized, ['down', 'critical', 'failed', 'cancelled']) => 'bg-rose-50 text-rose-800 ring-rose-600/15',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/15',
    };
@endphp

<span class="inline-flex items-center gap-1.5">
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.$classes]) }}>
        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
        {{ $displayLabel }}
    </span>
    @if ($description)
        <details class="group inline-flex flex-col">
            <summary class="flex h-6 w-6 cursor-pointer list-none items-center justify-center rounded-full text-xs font-bold text-slate-500 hover:bg-slate-100 focus-visible:outline-brand-500" aria-label="Penjelasan status {{ $status }}" title="Penjelasan status">i</summary>
            <span class="mt-1 hidden w-64 rounded-lg border border-slate-200 bg-white p-3 text-xs font-normal leading-5 text-slate-700 shadow-lg group-open:block" role="note">{{ $description }}</span>
        </details>
    @endif
</span>
