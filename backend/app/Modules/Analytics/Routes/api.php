<?php

use App\Modules\Analytics\Http\Controllers\MyReportStatsController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Analytics.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-4
Route::middleware(['auth:api', 'role:user'])
    ->get('me/report-stats', MyReportStatsController::class)
    ->name('me.report-stats');
