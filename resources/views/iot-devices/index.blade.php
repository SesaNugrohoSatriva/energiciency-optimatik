@extends('layouts.app')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold">Perangkat IoT</h1>
            <p class="text-sm text-gray-500">Kelola konfigurasi ESP32 yang terhubung ke sistem</p>
        </div>
        <a href="{{ route('iot-devices.create') }}" class="app-btn-primary">+ Tambah Perangkat</a>
    </div>

    @if(session('success'))
        <div class="app-card mb-4 border-l-4 border-green-500 p-4 text-green-800 bg-green-50">
            {{ session('success') }}
        </div>
    @endif

    <div class="app-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">MAC Address</th>
                    <th class="px-4 py-3">Device ID</th>
                    <th class="px-4 py-3">Mesin</th>
                    <th class="px-4 py-3">Interval Kirim</th>
                    <th class="px-4 py-3">Terakhir Online</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($devices as $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $device->mac_address }}</td>
                        <td class="px-4 py-3 font-medium">{{ $device->device_id }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $device->machine?->name ?? '—' }}
                            @if($device->machine_code)
                                <span class="text-xs text-gray-400">({{ $device->machine_code }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ number_format($device->send_interval_ms / 1000) }} detik</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $device->last_seen_at?->diffForHumans() ?? 'Belum pernah' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($device->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('iot-devices.edit', $device) }}" class="text-blue-600 hover:underline text-xs mr-3">Edit</a>
                            <form method="POST" action="{{ route('iot-devices.destroy', $device) }}" class="inline" onsubmit="return confirm('Hapus perangkat ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                            Belum ada perangkat. <a href="{{ route('iot-devices.create') }}" class="text-blue-600 hover:underline">Tambah sekarang</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="app-card mt-5 p-4 text-sm bg-blue-50 border border-blue-200">
        <p class="font-medium text-blue-800 mb-1">📡 Endpoint Konfigurasi ESP32</p>
        <code class="block text-xs bg-white border rounded px-3 py-2 mt-1">
            GET {{ url('/api/iot/config') }}<br>
            Headers: X-IOT-TOKEN, X-DEVICE-MAC
        </code>
        <p class="mt-2 text-xs text-blue-700">ESP32 fetch endpoint ini saat boot untuk mendapat device_id, machine_code, dan interval kirim.</p>
    </div>
@endsection
