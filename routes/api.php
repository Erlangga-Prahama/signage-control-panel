<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DeviceCommandController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DevicePlayerController;
use App\Http\Controllers\Api\PlaylistController;
use Illuminate\Support\Facades\Route;

// --- Admin auth (JWT) ---
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth.jwt')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // --- Devices ---
    Route::apiResource('devices', DeviceController::class);
    Route::post('/devices/{device}/commands', [DeviceCommandController::class, 'store']);

    // --- Contents ---
    Route::apiResource('contents', ContentController::class);

    // --- Playlists ---
    Route::apiResource('playlists', PlaylistController::class);
    Route::put('/playlists/{playlist}/items', [PlaylistController::class, 'syncItems']);
});

// --- Device client (authenticated via X-Device-Key header, not JWT) ---
Route::middleware('auth.device')->group(function () {
    Route::post('/device/heartbeat', [DeviceController::class, 'heartbeat']);
    Route::get('/device/player', [DevicePlayerController::class, 'show']);
    Route::post('/device/commands/{command}/ack', [DeviceCommandController::class, 'ack']);
});