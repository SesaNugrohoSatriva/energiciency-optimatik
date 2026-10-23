@extends('layouts.app')

@section('content')
    @php $editing = isset($device); @endphp

    <div class="mb-5">
        <h1 class="text-xl font-semibold">{{ $editing ? 'Edit Perangkat' : 'Tambah Perangkat IoT' }}</h1>
        <p class="text-sm text-gray-500">{{ $editing ? $device->device_id : 'Daftarkan ESP32 baru ke sistem' }}</p>
    </div>

    <div class="app-card max-w-xl p-6">
        <form method="POST" action="{{ $editing ? route('iot-devices.update', $device) : route('iot-devices.store') }}" class="grid gap-4">
            @csrf
            @if($editing) @method('PUT') @endif

            {{-- MAC Address --}}
            <label>
                <span class="app-label">MAC Address ESP32 <span class="text-red-500">*</span></span>
                <input class="app-input @error('mac_address') border-red-400 @enderror"
                    type="text" name="mac_address" maxlength="17" placeholder="AA:BB:CC:DD:EE:FF"
                    value="{{ old('mac_address', $device->mac_address ?? '') }}">
                <span class="text-xs text-gray-400 mt-1 block">Bisa lihat di Serial Monitor ESP32 saat boot (akan dicetak otomatis)</span>
                @error('mac_address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            {{-- Device ID --}}
            <label>
                <span class="app-label">Device ID <span class="text-red-500">*</span></span>
                <input class="app-input @error('device_id') border-red-400 @enderror"
                    type="text" name="device_id" maxlength="120" placeholder="energy-meter-01"
                    value="{{ old('device_id', $device->device_id ?? '') }}">
                <span class="text-xs text-gray-400 mt-1 block">Label unik perangkat, akan tersimpan di kolom device_id setiap record</span>
                @error('device_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            {{-- Mesin --}}
            <label>
                <span class="app-label">Mesin yang Dimonitor</span>
                <select class="app-input @error('machine_code') border-red-400 @enderror" name="machine_code">
                    <option value="">— Tidak terhubung ke mesin —</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->code }}"
                            @selected(old('machine_code', $device->machine_code ?? '') === $machine->code)>
                            {{ $machine->name }} · {{ $machine->code }}
                        </option>
                    @endforeach
                </select>
                @error('machine_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            {{-- Interval --}}
            <label>
                <span class="app-label">Interval Kirim Data <span class="text-red-500">*</span></span>
                <div class="flex items-center gap-2">
                    <input class="app-input @error('send_interval_ms') border-red-400 @enderror"
                        type="number" name="send_interval_ms" min="5" max="3600"
                        value="{{ old('send_interval_ms', isset($device) ? $device->send_interval_ms / 1000 : 30) }}">
                    <span class="text-sm text-gray-500 whitespace-nowrap">detik</span>
                </div>
                <span class="text-xs text-gray-400 mt-1 block">Minimum 5 detik, maksimum 3600 detik (1 jam)</span>
                @error('send_interval_ms') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            {{-- Notes --}}
            <label>
                <span class="app-label">Keterangan</span>
                <input class="app-input" type="text" name="notes" maxlength="255" placeholder="Opsional"
                    value="{{ old('notes', $device->notes ?? '') }}">
            </label>

            {{-- Status --}}
            @if($editing)
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" class="rounded"
                    @checked(old('is_active', $device->is_active ?? true))>
                <span class="text-sm">Perangkat aktif</span>
            </label>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="app-btn-primary">
                    {{ $editing ? 'Simpan Perubahan' : 'Tambah Perangkat' }}
                </button>
                <a href="{{ route('iot-devices.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>

    @if(!$editing)
    {{-- Petunjuk cara dapat MAC address --}}
    <div class="app-card mt-4 max-w-xl p-4 bg-yellow-50 border border-yellow-200 text-sm">
        <p class="font-medium text-yellow-800 mb-2">💡 Cara mendapatkan MAC Address ESP32</p>
        <p class="text-yellow-700 text-xs">Upload sketch ini ke ESP32, lalu buka Serial Monitor:</p>
        <pre class="mt-2 bg-white border rounded text-xs p-3 overflow-x-auto text-gray-700">#include &lt;WiFi.h&gt;
void setup() {
  Serial.begin(115200);
  Serial.println(WiFi.macAddress());
}
void loop() {}</pre>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    // Konversi input detik → ms sebelum submit
    document.querySelector('form').addEventListener('submit', function () {
        const input = this.querySelector('input[name="send_interval_ms"]');
        input.value = parseInt(input.value) * 1000;
    });
</script>
@endpush
