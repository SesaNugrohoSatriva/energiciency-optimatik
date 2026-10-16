<?php

namespace App\Http\Controllers;

use App\Models\EnergyRecord;
use App\Models\Machine;
use App\Services\EnergyAnalyticsService;
use Illuminate\Http\Request;

class EnergyMonitoringController extends Controller
{
    public function index(Request $request, EnergyAnalyticsService $analytics)
    {
        $filters = $analytics->filters($request);
        $query = $analytics->query($filters);
        $summary = $analytics->summary($query);
        $previousSummary = $analytics->previousPeriodSummary($filters);
        $trend = $analytics->trend($query, $filters['interval']);
        $records = (clone $query)->with([
            'machine',
            'process',
            'productionOrder.product',
            'productionBatch.productionOrder.product',
            'routingOperation',
        ])->latest('recorded_at')->paginate(20)->withQueryString();
        $machines = Machine::query()->orderBy('name')->get(['id', 'name', 'code']);
        $sources = EnergyRecord::query()->distinct()->orderBy('source')->pluck('source');
        $pageTitle = 'Energy Monitoring';
        $pageDescription = 'Tren, biaya, dan histori energi yang tercatat—dengan simulasi dibedakan dari data IoT.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Energy Monitoring']];

        return view('energy-records.index', compact(
            'filters',
            'summary',
            'previousSummary',
            'trend',
            'records',
            'machines',
            'sources',
            'pageTitle',
            'pageDescription',
            'breadcrumbs'
        ));
    }

    public function show(int $record)
    {
        $record = EnergyRecord::with([
            'machine',
            'process',
            'productionOrder.product',
            'productionBatch.productionOrder.product',
            'routingOperation',
        ])->findOrFail($record);
        $payload = is_array($record->payload) ? $this->redactPayload($record->payload) : null;
        $pageTitle = 'Catatan energi · '.$record->external_event_id;
        $pageDescription = 'Detail event energi tersimpan; payload sensitif disamarkan.';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Energy Monitoring', 'url' => route('energy-records.index')],
            ['label' => 'Detail'],
        ];

        return view('energy-records.show', compact('record', 'payload', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    private function redactPayload(array $payload): array
    {
        $safe = [];
        foreach ($payload as $key => $value) {
            if (preg_match('/secret|token|password|credential|authorization|api[_-]?key/i', (string) $key)) {
                $safe[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $safe[$key] = $this->redactPayload($value);
            } else {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }
}
