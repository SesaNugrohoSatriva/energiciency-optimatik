@extends('layouts.app')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-2"><x-ui.status-badge :status="$routing->status" context="structure" /><span class="text-xs text-slate-500">{{ $routing->operations->count() }} operasi</span></div>
        <div class="flex gap-2">
            <x-ui.button :href="route('products.show',[$product,'tab'=>'routing'])" variant="secondary">Semua versi</x-ui.button>
            @if ($routing->status === 'draft')
                <form method="POST" action="{{ route('products.routings.activate',[$product,$routing]) }}" onsubmit="return confirm('Aktifkan routing ini? Routing aktif sebelumnya akan diarsipkan.')">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit">Aktifkan versi</x-ui.button>
                </form>
            @endif
        </div>
    </div>

    @if ($errors->has('routing'))
        <x-ui.alert type="error" class="mb-4">{{ $errors->first('routing') }}</x-ui.alert>
    @endif
    @if ($errors->has('operations'))
        <x-ui.alert type="error" class="mb-4">{{ $errors->first('operations') }}</x-ui.alert>
    @endif

    <section class="app-card p-5 sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-bold text-navy-900">Process flow · {{ $routing->name }}</h2>
            <p class="mt-1 text-xs text-slate-500">Routing v{{ $routing->version }} · produk {{ $product->sku }} · urutan operasi yang tersimpan di database.</p>
        </div>
        @forelse ($routing->operations as $operation)
            <article class="relative ml-3 border-l-2 border-brand-100 pb-6 pl-7 last:border-transparent last:pb-0">
                <span class="absolute -left-[13px] top-0 flex h-6 w-6 items-center justify-center rounded-full border-2 border-brand-200 bg-white text-[10px] font-bold text-brand-800">{{ $operation->sequence }}</span>
                <div class="rounded-xl border border-slate-100 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div><h3 class="text-sm font-bold text-navy-900">{{ $operation->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $operation->process->code }} · {{ $operation->process->name }} · {{ $operation->machine?->name ?? $operation->process->machine?->name ?? 'Mesin belum ditentukan' }}</p></div>
                        <div class="flex flex-wrap gap-1.5">@if($operation->is_optional)<x-ui.status-badge status="Optional" />@else<x-ui.status-badge status="Wajib" />@endif @if($operation->requires_quality_inspection)<x-ui.status-badge status="Quality check" />@endif</div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                        <div><dt class="text-slate-500">Setup</dt><dd class="mt-1 font-semibold">{{ $operation->setup_time_minutes }} min</dd></div>
                        <div><dt class="text-slate-500">Siklus ideal</dt><dd class="mt-1 font-semibold">{{ $operation->ideal_cycle_seconds === null ? 'Belum ditentukan' : number_format((float)$operation->ideal_cycle_seconds,2,',','.').' detik/unit' }}</dd></div>
                        <div><dt class="text-slate-500">Tenaga kerja</dt><dd class="mt-1 font-semibold">{{ $operation->labor_required }} orang</dd></div>
                        <div><dt class="text-slate-500">Estimasi energi</dt><dd class="mt-1 font-semibold">{{ number_format((float)$operation->estimated_energy_kwh,3,',','.') }} kWh</dd></div>
                    </dl>
                    @if ($operation->work_instructions)<p class="mt-3 whitespace-pre-line text-xs leading-5 text-slate-600">{{ $operation->work_instructions }}</p>@endif
                    @if ($routing->status === 'draft')
                        <details class="mt-4 border-t border-slate-100 pt-3">
                            <summary class="cursor-pointer text-xs font-bold text-brand-700">Edit operasi</summary>
                            <form method="POST" action="{{ route('products.routings.operations.update',[$product,$routing,$operation]) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs font-semibold">Urutan (ubah melalui pengaturan urutan)<input name="sequence" type="number" min="1" required readonly value="{{ $operation->sequence }}" class="app-input mt-1"></label>
                                <label class="text-xs font-semibold">Proses<select name="process_id" required class="app-input mt-1">@foreach($processes as $process)<option value="{{ $process->id }}" @selected($process->id===$operation->process_id)>{{ $process->code }} · {{ $process->name }}</option>@endforeach</select></label>
                                <label class="text-xs font-semibold sm:col-span-2">Nama operasi<input name="name" maxlength="160" required value="{{ $operation->name }}" class="app-input mt-1"></label>
                                <label class="text-xs font-semibold">Mesin opsional<select name="machine_id" class="app-input mt-1"><option value="">Gunakan mesin proses</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected($machine->id===$operation->machine_id)>{{ $machine->code }} · {{ $machine->name }}</option>@endforeach</select></label>
                                <label class="text-xs font-semibold">Setup (menit)<input name="setup_time_minutes" type="number" min="0" required value="{{ $operation->setup_time_minutes }}" class="app-input mt-1"></label>
                                <label class="text-xs font-semibold">Siklus ideal (detik/unit)<input name="ideal_cycle_seconds" type="number" min="0" step="any" value="{{ $operation->ideal_cycle_seconds }}" class="app-input mt-1"></label>
                                <label class="text-xs font-semibold">Jumlah tenaga kerja<input name="labor_required" type="number" min="1" required value="{{ $operation->labor_required }}" class="app-input mt-1"></label>
                                <label class="text-xs font-semibold">Estimasi energi (kWh)<input name="estimated_energy_kwh" type="number" min="0" step="any" required value="{{ $operation->estimated_energy_kwh }}" class="app-input mt-1"></label>
                                <label class="flex items-center gap-2 text-xs font-semibold"><input name="requires_quality_inspection" type="checkbox" value="1" @checked($operation->requires_quality_inspection)> Memerlukan inspeksi kualitas</label>
                                <label class="flex items-center gap-2 text-xs font-semibold"><input name="is_optional" type="checkbox" value="1" @checked($operation->is_optional)> Operasi opsional</label>
                                <label class="text-xs font-semibold sm:col-span-2">Instruksi kerja<textarea name="work_instructions" rows="2" class="app-input mt-1">{{ $operation->work_instructions }}</textarea></label>
                                <div class="flex flex-wrap gap-2 sm:col-span-2"><x-ui.button type="submit">Simpan operasi</x-ui.button></div>
                            </form>
                            <form method="POST" action="{{ route('products.routings.operations.destroy',[$product,$routing,$operation]) }}" class="mt-2" onsubmit="return confirm('Hapus operasi dari draft routing?')">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-700">Hapus operasi</button></form>
                        </details>
                    @endif
                </div>
            </article>
        @empty
            <x-ui.empty-state title="Routing belum memiliki operasi" description="Tambahkan operasi untuk menentukan alur produksi produk." />
        @endforelse
    </section>

    @if ($routing->status === 'draft')
        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            @if ($routing->operations->count() > 1)
                <article class="app-card p-5 sm:p-6">
                    <h2 class="text-base font-bold text-navy-900">Atur urutan operasi</h2>
                    <p class="mt-1 text-xs text-slate-500">Pilih operasi yang menempati tiap urutan. Setiap operasi harus dipilih tepat satu kali.</p>
                    <form method="POST" action="{{ route('products.routings.operations.reorder',[$product,$routing]) }}" class="mt-4 space-y-3">
                        @csrf @method('PATCH')
                        @foreach ($routing->operations as $operation)
                            <label class="flex items-center gap-3 text-xs font-semibold text-slate-700"><span class="w-16">Urutan {{ $loop->iteration }}</span><select name="operations[]" class="app-input">@foreach($routing->operations as $choice)<option value="{{ $choice->id }}" @selected($choice->id===$operation->id)>{{ $choice->sequence }} · {{ $choice->name }}</option>@endforeach</select></label>
                        @endforeach
                        <x-ui.button type="submit">Simpan urutan</x-ui.button>
                    </form>
                </article>
            @endif
            <article class="app-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-navy-900">Tambah operasi</h2>
                <form method="POST" action="{{ route('products.routings.operations.store',[$product,$routing]) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <label class="text-xs font-semibold">Urutan<input name="sequence" type="number" min="1" required value="{{ old('sequence',($routing->operations->max('sequence') ?? 0)+1) }}" class="app-input mt-1"></label>
                    <label class="text-xs font-semibold">Proses<select name="process_id" required class="app-input mt-1"><option value="">Pilih proses</option>@foreach($processes as $process)<option value="{{ $process->id }}" @selected(old('process_id')==$process->id)>{{ $process->code }} · {{ $process->name }}</option>@endforeach</select></label>
                    <label class="text-xs font-semibold sm:col-span-2">Nama operasi<input name="name" required maxlength="160" value="{{ old('name') }}" placeholder="Nama tahapan pada routing ini" class="app-input mt-1"></label>
                    <label class="text-xs font-semibold">Mesin opsional<select name="machine_id" class="app-input mt-1"><option value="">Gunakan mesin proses</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected(old('machine_id')==$machine->id)>{{ $machine->code }} · {{ $machine->name }}</option>@endforeach</select></label>
                    <label class="text-xs font-semibold">Setup (menit)<input name="setup_time_minutes" type="number" min="0" required value="{{ old('setup_time_minutes',0) }}" class="app-input mt-1"></label>
                    <label class="text-xs font-semibold">Siklus ideal (detik/unit)<input name="ideal_cycle_seconds" type="number" min="0" step="any" value="{{ old('ideal_cycle_seconds') }}" class="app-input mt-1"></label>
                    <label class="text-xs font-semibold">Jumlah tenaga kerja<input name="labor_required" type="number" min="1" required value="{{ old('labor_required',1) }}" class="app-input mt-1"></label>
                    <label class="text-xs font-semibold">Estimasi energi (kWh)<input name="estimated_energy_kwh" type="number" min="0" step="any" required value="{{ old('estimated_energy_kwh',0) }}" class="app-input mt-1"></label>
                    <label class="flex items-center gap-2 text-xs font-semibold"><input name="requires_quality_inspection" type="checkbox" value="1"> Memerlukan inspeksi kualitas</label>
                    <label class="flex items-center gap-2 text-xs font-semibold"><input name="is_optional" type="checkbox" value="1"> Operasi opsional</label>
                    <label class="text-xs font-semibold sm:col-span-2">Instruksi kerja<textarea name="work_instructions" rows="2" class="app-input mt-1">{{ old('work_instructions') }}</textarea></label>
                    <div class="sm:col-span-2"><x-ui.button type="submit">Tambahkan operasi</x-ui.button></div>
                </form>
            </article>
        </section>
    @else
        <x-ui.alert type="info" class="mt-5">Routing aktif bersifat immutable. Untuk mengubahnya, buat draft baru dari tab Routing produk; operasi aktif akan disalin ke versi baru.</x-ui.alert>
    @endif
@endsection
