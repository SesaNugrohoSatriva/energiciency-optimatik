<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;
Route::post('/iot/telemetry', [TelemetryController::class, 'store'])->middleware('throttle:120,1');
