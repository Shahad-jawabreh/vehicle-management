<?php
use App\Http\Controllers\GeofencesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\{
    CompanyController,
    UserController,
    VehicleController,
    DeviceController,
    ProviderController,
    EventTypeController,
    UserEventController,
    CompanyZonesController
};


use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::prefix('users')->middleware(['auth:sanctum', 'role:admin,manager'])->group(function() {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});

Route::get('/users/name', [UserController::class, 'getUsersNames']);
Route::get('/users/profile', [UserController::class, 'getUsersProfile'])->middleware(['auth:sanctum']);
;

Route::apiResource('companies', CompanyController::class);
Route::apiResource('company-zones', CompanyZonesController::class);
Route::post('vehicles/{vehicle}/position', [VehicleController::class, 'updatePosition']);

Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('devices', DeviceController::class);
Route::apiResource('providers', ProviderController::class);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('events', EventTypeController::class);
});

Route::get('/active-event', [EventTypeController::class, 'activeEvent']);
Route::post('/test', [VehicleController::class, 'storetest']);
Route::apiResource('user-events', UserEventController::class)->only(['index', 'show', 'destroy']);
Route::post('/test', [VehicleController::class, 'storetest']);
