<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ProductRoutingController;
use App\Http\Controllers\BomVersionController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\EnergyMonitoringController;
use App\Http\Controllers\MachineAnalyticsController;
use App\Http\Controllers\IotDeviceController;
use App\Http\Controllers\TimeSeriesController;
Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('iot-devices', IotDeviceController::class);
    Route::get('/time-series', [TimeSeriesController::class, 'index'])->name('time-series.index');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('/costing', [DashboardController::class, 'costing'])->name('costing');
    Route::prefix('production-orders/{productionOrder}/batches')->name('production-orders.batches.')->group(function () {
        Route::post('/', [ProductionBatchController::class, 'store'])->name('store');
        Route::get('/{batch}', [ProductionBatchController::class, 'show'])->name('show');
        Route::patch('/{batch}/start', [ProductionBatchController::class, 'start'])->name('start');
        Route::patch('/{batch}/complete', [ProductionBatchController::class, 'complete'])->name('complete');
        Route::patch('/{batch}/cancel', [ProductionBatchController::class, 'cancel'])->name('cancel');
        Route::patch('/{batch}/materials/reserve', [ProductionBatchController::class, 'reserveMaterials'])->name('materials.reserve');
        Route::patch('/{batch}/materials/consume', [ProductionBatchController::class, 'consumeMaterials'])->name('materials.consume');
        Route::patch('/{batch}/materials/release', [ProductionBatchController::class, 'releaseMaterial'])->name('materials.release');
        Route::post('/{batch}/costs', [ProductionBatchController::class, 'recordCost'])->name('costs.store');
    });
    Route::prefix('products/{product}')->name('products.')->group(function () {
        Route::post('/boms', [BomVersionController::class, 'store'])->name('boms.store');
        Route::patch('/boms/{bom}/activate', [BomVersionController::class, 'activate'])->name('boms.activate');
        Route::post('/routings', [ProductRoutingController::class, 'store'])->name('routings.store');
        Route::get('/routings/{routing}', [ProductRoutingController::class, 'show'])->name('routings.show');
        Route::post('/routings/{routing}/operations', [ProductRoutingController::class, 'addOperation'])->name('routings.operations.store');
        Route::patch('/routings/{routing}/operations/reorder', [ProductRoutingController::class, 'reorder'])->name('routings.operations.reorder');
        Route::patch('/routings/{routing}/operations/{operation}', [ProductRoutingController::class, 'updateOperation'])->name('routings.operations.update');
        Route::delete('/routings/{routing}/operations/{operation}', [ProductRoutingController::class, 'destroyOperation'])->name('routings.operations.destroy');
        Route::patch('/routings/{routing}/activate', [ProductRoutingController::class, 'activate'])->name('routings.activate');
    });
    foreach (['machines' => 'Mesin', 'processes' => 'Proses', 'products' => 'Produk', 'production-orders' => 'Order Produksi', 'inventory' => 'Persediaan', 'maintenance-logs' => 'Pemeliharaan', 'energy-records' => 'Data Energi', 'bom-items' => 'Komponen BOM', 'oee-records' => 'Catatan OEE'] as $key => $label) {
        if ($key === 'machines') {
            Route::resource($key, ResourceController::class)->except(['show'])->parameters([$key => 'record'])->names($key);
            Route::get('/machines/{record}', [MachineAnalyticsController::class, 'show'])->name('machines.show');
        } elseif ($key === 'energy-records') {
            Route::resource($key, ResourceController::class)->except(['index', 'show'])->parameters([$key => 'record'])->names($key);
            Route::get('/energy-records', [EnergyMonitoringController::class, 'index'])->name('energy-records.index');
            Route::get('/energy-records/{record}', [EnergyMonitoringController::class, 'show'])->name('energy-records.show');
        } else {
            Route::resource($key, ResourceController::class)->parameters([$key => 'record'])->names($key);
        }
    }
});
require __DIR__ . '/auth.php';
