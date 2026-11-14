<?php

use App\Modules\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Notification.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-3
Route::middleware('auth:api')
    ->prefix('notifications')
    ->controller(NotificationController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('unread-count', 'unreadCount');
        Route::patch('read-all', 'markAllAsRead');
        Route::patch('{id}/read', 'markAsRead')->whereUuid('id');
    });
