<?php

use App\Modules\Auth\Http\Controllers\ForgotPasswordController;
use App\Modules\Auth\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Auth.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-2
Route::middleware('throttle:5,1')->prefix('auth')->group(function () {
    Route::post('forgot-password', ForgotPasswordController::class);
    Route::post('reset-password', ResetPasswordController::class);
});
