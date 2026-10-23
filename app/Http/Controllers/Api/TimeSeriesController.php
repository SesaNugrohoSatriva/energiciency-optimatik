<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyRecord;
use Illuminate\Http\Request;

class TimeSeriesController extends Controller
{
    public function index(Request $request)
    {
        $interval = $request->get('interval', 'day'); // minute, day, month
        $deviceId = $request->get('device_id');
        $date     = $request->get('date', now()->toDateString());

        $query = EnergyRecord::query()
            ->where('source', 'iot')
            ->whereNotNull('payload');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        // Filter waktu berdasarkan interval
        if ($interval === 'minute') {
            // Per menit: ambil data hari ini atau tanggal yang dipilih
            $query->whereDate('recorded_at', $date);
            $groupExpr = "strftime('%Y-%m-%d %H:%M', recorded_at)";
        } elseif ($interval === 'month') {
            // Per bulan: ambil data tahun ini atau tahun dari tanggal dipilih
            $year = substr($date, 0, 4);
            $query->whereYear('recorded_at', $year);
            $groupExpr = "strftime('%Y-%m', recorded_at)";
        } else {
            // Per hari: ambil data 30 hari terakhir
            $query->where('recorded_at', '>=', now()->subDays(30));
            $groupExpr = "strftime('%Y-%m-%d', recorded_at)";
        }

        $records = $query->orderBy('recorded_at')->get(['recorded_at', 'payload']);

        // Group dan ambil rata-rata per periode
        $grouped = $records->groupBy(function ($r) use ($interval) {
            if ($interval === 'minute') {
                return $r->recorded_at->format('Y-m-d H:i');
            } elseif ($interval === 'month') {
                return $r->recorded_at->format('Y-m');
            }
            return $r->recorded_at->format('Y-m-d');
        });

        $data = $grouped->map(function ($group, $period) {
            $currents = $group->map(fn($r) => (float)($r->payload['current_a'] ?? 0))->filter()->values();
            $powers   = $group->map(fn($r) => (float)($r->payload['power_w'] ?? 0))->filter()->values();

            return [
                'period'    => $period,
                'current_a' => $currents->isNotEmpty() ? round($currents->avg(), 3) : 0,
                'power_w'   => $powers->isNotEmpty()   ? round($powers->avg(), 1)   : 0,
            ];
        })->values();

        return response()->json(['ok' => true, 'data' => $data]);
    }
}
