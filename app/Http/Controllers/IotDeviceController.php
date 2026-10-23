<?php

namespace App\Http\Controllers;

use App\Models\IotDevice;
use App\Models\Machine;
use Illuminate\Http\Request;

class IotDeviceController extends Controller
{
    public function index()
    {
        $devices = IotDevice::with('machine')->latest()->get();
        return view('iot-devices.index', compact('devices'));
    }

    public function create()
    {
        $machines = Machine::orderBy('code')->get();
        return view('iot-devices.form', compact('machines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mac_address'      => 'required|string|size:17|unique:iot_devices,mac_address',
            'device_id'        => 'required|string|max:120|unique:iot_devices,device_id',
            'machine_code'     => 'nullable|string|exists:machines,code',
            'send_interval_ms' => 'required|integer|min:5|max:3600',
            'notes'            => 'nullable|string|max:255',
            'is_active'        => 'boolean',
        ]);

        $data['send_interval_ms'] = $data['send_interval_ms'] * 1000;
        IotDevice::create($data);

        return redirect()->route('iot-devices.index')
            ->with('success', 'Perangkat berhasil ditambahkan.');
    }

    public function edit(IotDevice $iotDevice)
    {
        $machines = Machine::orderBy('code')->get();
        return view('iot-devices.form', ['device' => $iotDevice, 'machines' => $machines]);
    }

    public function update(Request $request, IotDevice $iotDevice)
    {
        $data = $request->validate([
            'mac_address'      => 'required|string|size:17|unique:iot_devices,mac_address,' . $iotDevice->id,
            'device_id'        => 'required|string|max:120|unique:iot_devices,device_id,' . $iotDevice->id,
            'machine_code'     => 'nullable|string|exists:machines,code',
            'send_interval_ms' => 'required|integer|min:5|max:3600',
            'notes'            => 'nullable|string|max:255',
            'is_active'        => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['send_interval_ms'] = $data['send_interval_ms'] * 1000;
        $iotDevice->update($data);

        return redirect()->route('iot-devices.index')
            ->with('success', 'Perangkat berhasil diperbarui.');
    }

    public function destroy(IotDevice $iotDevice)
    {
        $iotDevice->delete();
        return redirect()->route('iot-devices.index')
            ->with('success', 'Perangkat dihapus.');
    }
}
