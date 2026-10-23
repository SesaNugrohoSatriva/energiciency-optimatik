<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\IotConfigController;
use App\Http\Controllers\Api\LiveMonitorController;
use App\Http\Controllers\Api\TimeSeriesController;

Route::post('/iot/telemetry', [TelemetryController::class, 'store'])->middleware('throttle:120,1');
Route::get('/iot/config', [IotConfigController::class, 'show'])->middleware('throttle:30,1');
Route::get('/iot/latest', [LiveMonitorController::class, 'index'])->middleware('throttle:60,1');
Route::get('/time-series', [TimeSeriesController::class, 'index'])->middleware('throttle:60,1');
