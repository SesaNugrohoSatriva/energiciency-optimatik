<?php

namespace App\Http\Controllers;

use App\Models\IotDevice;
use Illuminate\Http\Request;

class TimeSeriesController extends Controller
{
    public function index(Request $request)
    {
        $devices = IotDevice::query()
            ->where('is_active', true)
            ->orderBy('device_id')
            ->get(['device_id', 'machine_code']);

        $pageTitle       = 'IoT Monitoring';
        $pageDescription = 'Pemantauan historis arus dan daya dari telemetri perangkat IoT yang tersimpan.';
        $breadcrumbs     = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'IoT Monitoring'],
        ];

        return view('time-series.index', compact(
            'devices',
            'pageTitle',
            'pageDescription',
            'breadcrumbs',
        ));
    }
}
