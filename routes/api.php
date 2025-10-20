<?php
use App\Http\Controllers\GeofencesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\DeviceController;

Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('devices', DeviceController::class);
Route::apiResource('providers', ProviderController::class);
Route::apiResource('geofences', GeofencesController::class);
