<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Services\EnergyTelemetryIngestionService;
use Illuminate\Http\Request;
class TelemetryController extends Controller {
 public function store(Request $request, EnergyTelemetryIngestionService $ingestion){
  $expected=(string)env('IOT_INGEST_TOKEN',''); $provided=(string)$request->header('X-IOT-TOKEN','');
  abort_if($expected==='' || !hash_equals($expected,$provided),401,'Token perangkat tidak valid.');
  $data=$request->validate(['device_id'=>'required|string|max:120','external_event_id'=>'required|string|max:180','recorded_at'=>'required|date','kwh'=>'required|numeric|min:0','machine_code'=>'nullable|string|exists:machines,code','process_id'=>'nullable|integer|exists:processes,id','production_order_id'=>'nullable|integer|exists:production_orders,id','payload'=>'nullable|array']);
  $machine=isset($data['machine_code'])?Machine::where('code',$data['machine_code'])->first():Machine::where('device_id',$data['device_id'])->first();
  $tariff=(float)env('ENERGY_TARIFF_PER_KWH',1640);
  $record=$ingestion->ingest($data,$machine,$tariff);
  return response()->json(['ok'=>true,'duplicate'=>!$record->wasRecentlyCreated,'data'=>['id'=>$record->id,'kwh'=>$record->kwh,'cost'=>$record->cost]],$record->wasRecentlyCreated?201:200);
 }
}
