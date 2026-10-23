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

        $pageTitle       = 'Time Series IoT';
        $pageDescription = 'Visualisasi arus dan daya dari data telemetri IoT, dapat difilter per menit, hari, atau bulan.';
        $breadcrumbs     = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Time Series IoT'],
        ];

        return view('time-series.index', compact(
            'devices',
            'pageTitle',
            'pageDescription',
            'breadcrumbs',
        ));
    }
}
