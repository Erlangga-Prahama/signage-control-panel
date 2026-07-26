<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/login', fn () => view('dashboard.login'))->name('login');

Route::view('/dashboard', 'dashboard.index')->name('dashboard.devices');
Route::view('/dashboard/contents', 'dashboard.contents')->name('dashboard.contents');
Route::view('/dashboard/playlists', 'dashboard.playlists')->name('dashboard.playlists');

// The signage screen itself. Point a browser (or the Electron client) at
// /player/{device_key}. No login needed here — the device_key IS the
// device's credential.
Route::get('/player/{deviceKey}', [\App\Http\Controllers\DeviceClientController::class, 'show'])
    ->name('player.show');