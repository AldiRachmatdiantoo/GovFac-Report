<?php

use App\Modules\Verification\Http\Controllers\AdminReportController;
use App\Modules\Verification\Http\Controllers\AdminReviewListController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Verification.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-15
Route::middleware(['auth:api', 'role:admin'])->prefix('admin/reports')->group(function (): void {
    Route::get('{id}', [AdminReportController::class, 'show'])->whereNumber('id')->name('admin.reports.show');
    Route::post('{id}/approve', [AdminReportController::class, 'approve'])->whereNumber('id')->name('admin.reports.approve');
    Route::post('{id}/reject', [AdminReportController::class, 'reject'])->whereNumber('id')->name('admin.reports.reject');
});

// PBI-16
Route::middleware(['auth:api', 'role:admin'])->prefix('admin/reports')->group(function (): void {
    Route::get('queue', [AdminReviewListController::class, 'queue'])->name('admin.reports.queue');
    Route::get('history', [AdminReviewListController::class, 'history'])->name('admin.reports.history');
});
