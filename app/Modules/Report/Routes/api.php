<?php

use App\Modules\Report\Http\Controllers\CreateReportController;
use App\Modules\Report\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Report.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-5
Route::middleware('auth:api')->group(function (): void {
    Route::post('reports', CreateReportController::class)->middleware('role:user')->name('reports.store');
    Route::get('tags', TagController::class)->name('tags.index');
});
