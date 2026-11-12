<?php

use App\Modules\Auth\Http\Controllers\ForgotPasswordController;
use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\MeController;
use App\Modules\Auth\Http\Controllers\RegisterController;
use App\Modules\Auth\Http\Controllers\ResetPasswordController;
use App\Modules\Auth\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Auth.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
|
| Pembatas permintaan (throttle:auth-*) didefinisikan di AuthServiceProvider,
| satu jatah per endpoint supaya tidak saling menghabiskan.
*/

// PBI-1
Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class)->middleware('throttle:auth-register');
    Route::post('login', LoginController::class)->middleware('throttle:auth-login');

    // Sengaja tanpa auth:api: refresh harus menerima token yang sudah kedaluwarsa,
    // dan logout harus tetap berhasil walau tokennya sudah mati.
    Route::post('refresh', [TokenController::class, 'refresh']);
    Route::post('logout', [TokenController::class, 'logout']);

    Route::get('me', MeController::class)->middleware('auth:api');
});

// PBI-2
Route::prefix('auth')->group(function () {
    Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:auth-forgot-password');
    Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:auth-reset-password');
});
