@extends('layouts.app')

@section('content')
    @php
        $editing = $record->exists;
        $lockedOrder = $key === 'production-orders' && $editing && $record->batches()->exists();
        $lockedOrderFields = ['product_id','bom_id','product_routing_id','status','actual_quantity','good_quantity'];
        $schemas = [
            'machines' => ['code'=>'Kode mesin','name'=>'Nama mesin','category'=>'Kategori','location'=>'Lokasi/workstation','capacity'=>'Kapasitas','capacity_unit'=>'Satuan kapasitas','rated_kw'=>'Daya nominal (kW)','target_oee'=>'Target OEE (%)','status'=>'Status operasional','device_id'=>'ID perangkat'],
            'processes' => ['code'=>'Kode proses','name'=>'Nama proses','machine_id'=>'Mesin/workstation utama','setup_time_minutes'=>'Waktu setup (menit)','ideal_cycle_seconds'=>'Siklus ideal (detik/unit)','labor_required'=>'Tenaga kerja per operasi','capacity'=>'Kapasitas proses','capacity_unit'=>'Satuan kapasitas','estimated_kwh'=>'Estimasi energi (kWh)','work_instructions'=>'Instruksi kerja','quality_criteria'=>'Kriteria kualitas','description'=>'Deskripsi'],
            'products' => ['sku'=>'SKU / kode produk','name'=>'Nama produk','description'=>'Deskripsi','category'=>'Kategori','base_unit'=>'Satuan dasar','is_active'=>'Status produk','batch_size'=>'Ukuran batch standar','target_oee'=>'Target OEE (%)','estimated_production_minutes'=>'Estimasi durasi (menit)','estimated_energy_kwh'=>'Estimasi energi (kWh)','simulation_status'=>'Keterangan simulasi','material_cost'=>'Biaya material dasar','labor_cost'=>'Biaya tenaga kerja','overhead_cost'=>'Biaya overhead'],
            'production-orders' => ['order_number'=>'Nomor order','product_id'=>'Produk','bom_id'=>'Snapshot BOM aktif','product_routing_id'=>'Snapshot routing aktif','status'=>'Status','planned_quantity'=>'Kuantitas rencana','actual_quantity'=>'Kuantitas aktual','good_quantity'=>'Kuantitas baik','scheduled_at'=>'Tanggal rencana'],
            'inventory' => ['sku'=>'SKU','name'=>'Nama item','category'=>'Kategori','quantity'=>'Jumlah stok','threshold'=>'Batas minimum','unit'=>'Satuan','unit_cost'=>'Biaya per unit','location'=>'Lokasi'],
            'maintenance-logs' => ['machine_id'=>'Mesin','issue'=>'Masalah','priority'=>'Prioritas','status'=>'Status','scheduled_at'=>'Tanggal jadwal','notes'=>'Catatan'],
            'energy-records' => ['machine_id'=>'Mesin terkait','process_id'=>'Proses terkait','production_order_id'=>'Order produksi terkait','production_batch_id'=>'Batch produksi terkait','routing_operation_id'=>'Operasi routing terkait','device_id'=>'ID perangkat','kwh'=>'Energi (kWh)','tariff_per_kwh'=>'Tarif per kWh','recorded_at'=>'Waktu pengukuran','source'=>'Sumber data','external_event_id'=>'ID event eksternal'],
            'bom-items' => ['product_id'=>'Produk','bom_id'=>'Versi BOM (draft)','inventory_item_id'=>'Item inventory terkait','component_sku'=>'SKU komponen','component_name'=>'Nama komponen','quantity_per_unit'=>'Jumlah per output BOM','scrap_allowance_percent'=>'Allowance scrap (%)','unit'=>'Satuan','unit_cost'=>'Biaya referensi per unit','supplier'=>'Pemasok'],
            'oee-records' => ['machine_id'=>'Mesin','production_batch_id'=>'Batch produksi terkait','routing_operation_id'=>'Operasi routing terkait','recorded_on'=>'Tanggal catatan','planned_minutes'=>'Waktu terencana (menit)','operating_minutes'=>'Waktu operasi (menit)','ideal_cycle_minutes'=>'Siklus ideal (menit/unit)','total_output'=>'Total output','good_output'=>'Output baik','notes'=>'Catatan'],
        ];
        $required = [
            'machines'=>['code','name','status'],'processes'=>['code','name'],'products'=>['sku','name','base_unit','is_active','batch_size'],
            'production-orders'=>['order_number','product_id','status','planned_quantity'],
            'inventory'=>['sku','name','quantity','threshold','unit','unit_cost'],
            'maintenance-logs'=>['machine_id','issue','priority','status'],
            'energy-records'=>['kwh','tariff_per_kwh','recorded_at','source'],
            'bom-items'=>['product_id','component_sku','component_name','quantity_per_unit','unit','unit_cost'],
            'oee-records'=>['machine_id','recorded_on','planned_minutes','operating_minutes','ideal_cycle_minutes','total_output','good_output'],
        ][$key];
        $selectOptions = [
            'machine_id' => $references['machines']->mapWithKeys(fn ($item) => [$item->id => $item->code.' · '.$item->name])->all(),
            'process_id' => $references['processes']->mapWithKeys(fn ($item) => [$item->id => $item->code.' · '.$item->name])->all(),
            'product_id' => $references['products']->mapWithKeys(fn ($item) => [$item->id => $item->sku.' · '.$item->name])->all(),
            'production_order_id' => $references['productionOrders']->mapWithKeys(fn ($item) => [$item->id => $item->order_number])->all(),
            'production_batch_id' => $references['productionBatches']->mapWithKeys(fn ($item) => [$item->id => $item->batch_number])->all(),
            'routing_operation_id' => $references['routingOperations']->mapWithKeys(fn ($item) => [$item->id => $item->name.' · urutan '.$item->sequence])->all(),
            'bom_id' => $references['boms']->filter(fn ($bom) => $key === 'bom-items' ? ($bom->status === 'draft' || $bom->id === $record->bom_id) : ($bom->status === 'active' || $bom->id === $record->bom_id))->mapWithKeys(fn ($bom) => [$bom->id => $bom->product->sku.' · '.$bom->bom_number.' · '.$bom->status])->all(),
            'product_routing_id' => $references['productRoutings']->filter(fn ($routing) => $routing->status === 'active' || $routing->id === $record->product_routing_id)->mapWithKeys(fn ($routing) => [$routing->id => $routing->product->sku.' · '.$routing->name.' v'.$routing->version])->all(),
            'inventory_item_id' => $references['inventoryItems']->mapWithKeys(fn ($item) => [$item->id => $item->sku.' · '.$item->name])->all(),
        ];
        $numberFields = ['rated_kw','target_oee','estimated_kwh','estimated_energy_kwh','estimated_production_minutes','setup_time_minutes','ideal_cycle_seconds','labor_required','capacity','batch_size','material_cost','labor_cost','overhead_cost','planned_quantity','actual_quantity','good_quantity','quantity','threshold','unit_cost','kwh','tariff_per_kwh','quantity_per_unit','scrap_allowance_percent','planned_minutes','operating_minutes','ideal_cycle_minutes','total_output','good_output'];
        $wideFields = ['description','simulation_status','notes','work_instructions','quality_criteria'];
        $commonHints = [
            'code' => 'Gunakan kode unik dan konsisten agar mudah dicari serta dirujuk di proses lain.',
            'sku' => 'Kode unik untuk mengidentifikasi item; gunakan format yang konsisten dengan katalog internal.',
            'name' => 'Nama yang mudah dikenali oleh operator dan tim perencanaan.',
            'machine_id' => 'Pilihan berasal dari master mesin. Tautan ini membantu menelusuri proses atau pekerjaan pemeliharaan.',
            'process_id' => 'Pilihan berasal dari master proses yang sudah terdaftar.',
            'product_id' => 'Pilih produk yang menjadi dasar struktur atau order ini.',
            'production_order_id' => 'Tautkan hanya jika catatan ini memang berasal dari order tersebut.',
            'production_batch_id' => 'Opsional. Tautan batch membuat energi/OEE dan biaya dapat ditelusuri pada batch terkait.',
            'routing_operation_id' => 'Opsional. Pilih operasi dari routing produk yang sesuai; tautan ini tidak mengalokasikan data secara otomatis.',
            'bom_id' => 'Pilih versi BOM milik produk yang sama. Hanya draft yang dapat diubah; order menyimpan snapshot struktur saat dibuat.',
            'product_routing_id' => 'Pilih routing aktif milik produk. Snapshot order akan mempertahankan versi yang dipilih.',
            'inventory_item_id' => 'Tautkan ke master inventory yang benar agar kebutuhan batch dapat direservasi dan dikonsumsi.',
            'capacity' => 'Kapasitas nominal menurut satuan yang dipilih; nilai ini tidak otomatis mengatur penjadwalan.',
            'capacity_unit' => 'Satuan yang digunakan untuk menjelaskan kapasitas, misalnya unit/jam.',
            'rated_kw' => 'Daya nominal mesin dalam kW, bukan konsumsi energi aktual. Pembacaan energi dicatat dalam kWh.',
            'target_oee' => 'Target OEE dalam persen (0–100); ini target acuan, bukan hasil pengukuran.',
            'setup_time_minutes' => 'Waktu persiapan operasi dalam menit. Nilai ini merupakan estimasi standar.',
            'ideal_cycle_seconds' => 'Waktu ideal menghasilkan satu unit dalam detik; dipakai sebagai standar proses, bukan waktu aktual.',
            'ideal_cycle_minutes' => 'Waktu siklus ideal per unit dalam menit. Nilai ini menjadi pembagi performa pada perhitungan OEE.',
            'labor_required' => 'Jumlah tenaga kerja yang dibutuhkan untuk operasi/proses sesuai estimasi routing.',
            'estimated_kwh' => 'Estimasi energi dalam kWh, bukan pembacaan sensor atau konsumsi aktual.',
            'estimated_energy_kwh' => 'Estimasi energi untuk produk dalam kWh. Tidak menggantikan catatan energi aktual.',
            'estimated_production_minutes' => 'Estimasi total durasi produksi dalam menit; bukan jadwal atau waktu aktual.',
            'batch_size' => 'Ukuran batch standar dalam unit produk. Order/batch aktual tetap direncanakan melalui workflow batch.',
            'planned_quantity' => 'Jumlah unit yang direncanakan. Kuantitas ini membatasi pembagian batch dan perhitungan kebutuhan material.',
            'actual_quantity' => 'Jumlah output aktual yang benar-benar dihasilkan. Masukkan hasil pengukuran/pencatatan produksi.',
            'good_quantity' => 'Jumlah output yang lolos kriteria kualitas; tidak boleh melebihi output aktual.',
            'quantity' => 'Jumlah stok fisik dalam satuan item. Perubahan saldo dicatat sebagai transaksi inventory.',
            'threshold' => 'Batas minimum stok dalam satuan item; stok pada atau di bawah nilai ini perlu ditinjau.',
            'unit' => 'Satuan untuk kuantitas item. Perubahan satuan dibatasi jika item sudah dipakai BOM atau memiliki transaksi.',
            'unit_cost' => 'Biaya referensi per satu unit dalam rupiah. Nilai baru tidak mengubah snapshot biaya transaksi lama.',
            'tariff_per_kwh' => 'Tarif dalam rupiah untuk setiap kWh. Sistem menghitung biaya catatan sebagai kWh × tarif.',
            'kwh' => 'Konsumsi energi dalam kilowatt-hour (kWh), bukan daya nominal dalam kW.',
            'recorded_at' => 'Tanggal dan waktu pembacaan energi terjadi. Pertahankan waktu sumber agar urutan telemetri dapat ditelusuri.',
            'recorded_on' => 'Tanggal catatan OEE; gunakan tanggal periode operasi yang sedang diukur.',
            'source' => 'Pilih asal data yang benar. Simulation adalah data simulasi, bukan pembacaan sensor aktual; label IoT bukan status online perangkat.',
            'external_event_id' => 'ID unik dari sumber eksternal untuk mengenali pengiriman ulang event. Jangan mengganti identitas event yang sama.',
            'device_id' => 'ID perangkat sumber jika tersedia. ID ini sendiri tidak membuktikan perangkat sedang online.',
            'quantity_per_unit' => 'Jumlah komponen untuk menghasilkan output BOM sesuai basis output versi BOM; pastikan satuannya sesuai item.',
            'scrap_allowance_percent' => 'Persentase cadangan scrap (0–100%) yang ditambahkan pada kebutuhan komponen; isi 0 jika tidak ada allowance.',
            'output_quantity' => 'Jumlah produk yang dihasilkan oleh satu basis resep BOM.',
            'planned_minutes' => 'Durasi produksi yang direncanakan dalam menit untuk periode catatan OEE.',
            'operating_minutes' => 'Waktu mesin benar-benar beroperasi dalam menit; tidak boleh melebihi waktu terencana.',
            'total_output' => 'Jumlah seluruh output (baik dan reject) selama periode catatan.',
            'notes' => 'Catatan tambahan untuk menjelaskan konteks, pengecualian, atau alasan perubahan.',
            'status' => match ($key) {
                'machines' => 'Status operasional yang dicatat pengguna; tidak menunjukkan heartbeat atau koneksi IoT.',
                'production-orders' => 'Status order pada data ini. Setelah batch dibuat, status dan output dikelola oleh workflow batch.',
                'maintenance-logs' => 'Tahap penanganan pekerjaan pemeliharaan. Status ini tidak mengubah status koneksi mesin.',
                default => 'Pilih status sesuai tahap kerja aktual yang tercatat.',
            },
            'scheduled_at' => 'Tanggal rencana order atau pemeliharaan. Kosongkan jika belum dijadwalkan; tanggal ini tidak mengubah status proses.',
            'location' => 'Lokasi mesin atau penyimpanan item jika sudah ditentukan; field ini opsional.',
            'priority' => 'Tingkat urgensi pekerjaan pemeliharaan, bukan status ketersediaan mesin.',
            'supplier' => 'Pemasok komponen jika sudah diketahui; field ini opsional.',
        ];
        $entityHints = [
            'machines' => ['location' => 'Lokasi mesin atau workstation, misalnya area/line produksi.', 'device_id' => 'ID perangkat IoT yang dikaitkan jika tersedia; keberadaan ID bukan bukti perangkat online.'],
            'processes' => ['work_instructions' => 'Instruksi kerja yang perlu diikuti operator untuk menjalankan proses.', 'quality_criteria' => 'Kriteria yang digunakan untuk menilai hasil proses.', 'description' => 'Konteks tambahan mengenai fungsi atau cakupan proses.'],
            'products' => ['base_unit' => 'Satuan dasar produk, misalnya pcs atau kg; digunakan saat menampilkan kuantitas.', 'is_active' => 'Produk nonaktif tidak disarankan untuk dipilih pada proses baru.', 'material_cost' => 'Estimasi biaya material per unit produk; bukan biaya aktual batch.', 'labor_cost' => 'Estimasi biaya tenaga kerja per unit produk; bukan biaya aktual batch.', 'overhead_cost' => 'Estimasi overhead per unit produk; bukan biaya aktual batch.', 'simulation_status' => 'Jelaskan jika data produk bersifat simulasi atau mempunyai keterbatasan sumber.'],
            'production-orders' => ['order_number' => 'Identitas unik order yang digunakan pada penelusuran produksi.', 'scheduled_at' => 'Tanggal rencana pelaksanaan order; bukan bukti produksi sudah dimulai.'],
            'inventory' => ['location' => 'Lokasi penyimpanan item jika sudah ditentukan.', 'category' => 'Pengelompokan item untuk membantu pencarian.'],
            'maintenance-logs' => ['issue' => 'Ringkasan masalah atau pekerjaan pemeliharaan yang perlu ditangani.', 'priority' => 'Tingkat urgensi pekerjaan, bukan status ketersediaan mesin.', 'scheduled_at' => 'Tanggal pekerjaan direncanakan. Kosongkan jika belum dijadwalkan.'],
            'energy-records' => ['process_id' => 'Opsional. Proses yang terkait dengan pembacaan; tidak mengubah pembacaan energi.', 'payload' => 'Muatan mentah perangkat untuk audit. Sistem menyimpannya sebagai JSON; hindari memasukkan data rahasia.', 'external_event_id' => 'Gunakan ID event sumber yang stabil agar event yang dikirim ulang dapat dikenali.'],
            'bom-items' => ['supplier' => 'Pemasok komponen jika sudah diketahui; field ini opsional.', 'component_sku' => 'Kode komponen pada master/katalog. Jika inventory ditautkan, kode akan mengikuti item inventory.', 'component_name' => 'Nama komponen yang dikenali tim produksi. Jika inventory ditautkan, nama mengikuti item tersebut.'],
            'oee-records' => ['machine_id' => 'Mesin yang diukur. Hasil OEE dihitung dari waktu dan output pada catatan ini.', 'notes' => 'Opsional. Catat konteks downtime atau pengecualian periode bila relevan.'],
        ];
    @endphp

    <div class="mb-5">
        <a href="{{ route($key.'.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-900">
            <span aria-hidden="true">←</span> Kembali ke {{ $label }}
        </a>
    </div>

    <form method="POST" action="{{ $editing ? route($key.'.update', $record->id) : route($key.'.store') }}" class="app-card max-w-4xl p-5 sm:p-7">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="mb-6 border-b border-slate-100 pb-5">
            <h2 class="text-base font-bold text-navy-900">{{ $editing ? 'Perbarui informasi' : 'Informasi baru' }}</h2>
            <p class="mt-1 text-xs text-slate-500"><span class="text-rose-600">*</span> Wajib diisi. Validasi dilakukan kembali oleh server sebelum data disimpan.</p>
        </div>
        @if ($lockedOrder)
            <x-ui.information title="Sebagian field order dikendalikan workflow batch" type="warning" class="mb-5">
                <p>Produk, snapshot BOM/routing, status, dan output tidak dapat diubah dari form ini setelah batch dibuat. Kelola progres dan output melalui halaman batch.</p>
            </x-ui.information>
        @endif

        <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
            @foreach ($schemas[$key] as $field => $caption)
                @php
                    $isRequired = in_array($field, $required, true);
                    $value = old($field, $record->{$field});
                    $type = $field === 'recorded_at' ? 'datetime-local' : (in_array($field, ['recorded_on','scheduled_at'], true) ? 'date' : (in_array($field, $numberFields, true) ? 'number' : 'text'));
                    if ($field === 'recorded_at' && $value) $value = \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i');
                    $isWide = in_array($field, $wideFields, true);
                    $hint = $entityHints[$key][$field] ?? $commonHints[$field] ?? null;
                    $describedBy = trim(($hint ? $field.'-hint ' : '').($errors->has($field) ? $field.'-error' : ''));
                @endphp
                <div class="{{ $isWide ? 'sm:col-span-2' : '' }}">
                    <label for="{{ $field }}" class="mb-1.5 block text-sm font-semibold text-navy-900">
                        {{ $caption }} @if ($isRequired)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">wajib diisi</span>@endif
                    </label>

                    @if ($lockedOrder && in_array($field, $lockedOrderFields, true))
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        <p class="app-input flex min-h-11 items-center bg-slate-50 text-slate-600">{{ $selectOptions[$field][(string)$value] ?? ($value ?? '—') }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">Dikendalikan oleh batch atau snapshot order.</p>
                    @elseif ($field === 'is_active')
                        <select id="{{ $field }}" name="{{ $field }}" required class="app-input" aria-invalid="@error($field) true @else false @enderror" aria-describedby="{{ $describedBy ?: null }}">
                            <option value="1" @selected((string)$value === '1')>Aktif</option>
                            <option value="0" @selected((string)$value === '0')>Nonaktif</option>
                        </select>
                    @elseif (in_array($field, ['status','priority','source'], true))
                        @php
                            $options = match ($field) {
                                'status' => match ($key) {
                                    'machines' => ['Normal','Monitor','Down'],
                                    'production-orders' => ['Planned','In Progress','Completed','Delayed'],
                                    'maintenance-logs' => ['Open','Monitor','Planned','In Progress','Completed'],
                                    default => [],
                                },
                                'priority' => ['Low','Medium','High','Critical'],
                                'source' => ['simulation','manual','iot'],
                            };
                        @endphp
                        <select id="{{ $field }}" name="{{ $field }}" class="app-input @error($field) border-rose-500 @enderror" @if($isRequired) required @endif aria-invalid="@error($field) true @else false @enderror" aria-describedby="{{ $describedBy ?: null }}">
                            <option value="">Pilih {{ strtolower($caption) }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option }}" @selected((string) $value === $option)>{{ strtolower($option) === 'iot' ? 'IoT' : str($option)->replace('_',' ')->title() }}</option>
                            @endforeach
                        </select>
                    @elseif (isset($selectOptions[$field]))
                        <select id="{{ $field }}" name="{{ $field }}" class="app-input @error($field) border-rose-500 @enderror" @if($isRequired) required @endif aria-invalid="@error($field) true @else false @enderror" aria-describedby="{{ $describedBy ?: null }}">
                            @unless ($isRequired)<option value="">Tidak ditautkan</option>@else<option value="">Pilih {{ strtolower($caption) }}</option>@endunless
                            @foreach ($selectOptions[$field] as $optionId => $optionLabel)
                                <option value="{{ $optionId }}" @selected((string) $value === (string) $optionId)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif (in_array($field, $wideFields, true))
                        <textarea id="{{ $field }}" name="{{ $field }}" rows="3" class="app-input min-h-24 @error($field) border-rose-500 @enderror" @if($isRequired) required @endif aria-invalid="@error($field) true @else false @enderror" aria-describedby="{{ $describedBy ?: null }}">{{ $value }}</textarea>
                    @else
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ $value }}" @if($type === 'number') step="any" @endif @if($isRequired) required @endif @if($field === 'kwh' || str_contains($field, 'cost') || str_contains($field, 'quantity') || str_ends_with($field, '_minutes') || $field === 'rated_kw' || $field === 'estimated_kwh') min="0" @endif class="app-input @error($field) border-rose-500 @enderror" aria-invalid="@error($field) true @else false @enderror" aria-describedby="{{ $describedBy ?: null }}">
                    @endif

                    @if ($hint)
                        <x-ui.form-hint id="{{ $field }}-hint">{{ $hint }}</x-ui.form-hint>
                    @endif
                    @error($field)
                        <p id="{{ $field }}-error" class="mt-1.5 text-xs font-medium text-rose-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <x-ui.button :href="route($key.'.index')" variant="secondary">Batal</x-ui.button>
            <x-ui.button type="submit">
                {{ $editing ? 'Simpan perubahan' : 'Simpan data' }}
            </x-ui.button>
        </div>
    </form>
@endsection
