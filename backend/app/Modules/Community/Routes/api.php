<?php

use App\Modules\Community\Http\Controllers\FeedController;
use App\Modules\Community\Http\Controllers\ReportLikeController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Community.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-11, PBI-12 (filter feed lewat query string)
Route::middleware('auth:api')->group(function (): void {
    Route::get('feed', FeedController::class)->name('feed.index');

    Route::middleware('role:user')->group(function (): void {
        Route::put('reports/{id}/like', [ReportLikeController::class, 'store'])->whereNumber('id')->name('reports.like');
        Route::delete('reports/{id}/like', [ReportLikeController::class, 'destroy'])->whereNumber('id')->name('reports.unlike');
    });
});
