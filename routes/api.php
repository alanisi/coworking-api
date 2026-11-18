<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\SpaceTypeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (RF11)[cite: 2]
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con Sanctum[cite: 3]
Route::middleware('auth:sanctum')->group(function () {
    // Auth (RF12, RF13)[cite: 2]
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Catálogo público para usuarios autenticados (RF01)[cite: 2]
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/spaces/{space}', [SpaceController::class, 'show']);

    // Cliente (RF02, RF03)[cite: 2]
    Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    // Staff / Recepción y Admin (RF04, RF05, RF06, RF07)[cite: 2]
    Route::middleware('role:staff|admin')->group(function () {
        Route::get('/reservations/pending', [ReservationController::class, 'pending']);
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'approveOrReject']);
        Route::patch('/reservations/{reservation}/complete', [ReservationController::class, 'complete']);
        Route::get('/spaces-occupancy', [SpaceController::class, 'occupancyByDate']);
    });

    // Administrador (RF08, RF09, RF10)[cite: 2]
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('/space-types', SpaceTypeController::class)->except(['show']);
        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);

        Route::apiResource('/users', UserController::class)->except(['show']);
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
    });
});