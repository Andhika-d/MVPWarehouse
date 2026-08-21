<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ubah-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/ubah-password', [AuthController::class, 'changePassword']);

    Route::get('/notifications/poll', [NotificationController::class, 'poll']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/admin/impersonation/stop', [AdminController::class, 'stopImpersonation']);

    Route::middleware('role:gudang')->group(function () {
        Route::get('/gudang/dashboard', [DashboardController::class, 'gudangDashboard']);
        Route::get('/gudang/stock', [DashboardController::class, 'gudangStock']);

        Route::get('/gudang/penerimaan', [GudangController::class, 'penerimaanIndex']);
        Route::post('/gudang/penerimaan', [GudangController::class, 'penerimaanStore'])->middleware('throttle:20,1');

        Route::get('/gudang/barang-keluar', [GudangController::class, 'barangKeluarIndex']);
        Route::post('/gudang/barang-keluar', [GudangController::class, 'barangKeluarStore'])->middleware('throttle:20,1');

        Route::get('/gudang/movements', [GudangController::class, 'movementsIndex']);

        Route::get('/gudang/request-barang', [RequestController::class, 'create']);
        Route::post('/gudang/request-barang', [RequestController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/gudang/history', [RequestController::class, 'history']);
        Route::get('/gudang/history/export/pdf', [RequestController::class, 'exportHistoryPdf']);
        Route::get('/gudang/history/export/excel', [RequestController::class, 'exportHistoryExcel']);
        Route::get('/gudang/history/{request}', [RequestController::class, 'detail']);
    });

    Route::middleware('role:hr')->group(function () {
        Route::get('/hr/dashboard', [DashboardController::class, 'hrDashboard']);
        Route::get('/hr/approval', [RequestController::class, 'approvalIndex']);
        Route::get('/hr/approval/export/pdf', [RequestController::class, 'exportApprovalPdf']);
        Route::get('/hr/approval/export/excel', [RequestController::class, 'exportApprovalExcel']);
        Route::get('/hr/requests/{request}', [RequestController::class, 'approvalDetail']);
        Route::post('/hr/requests/{request}/approve', [RequestController::class, 'approve']);
        Route::post('/hr/requests/{request}/reject', [RequestController::class, 'reject']);
        Route::post('/hr/requests/{request}/delay', [RequestController::class, 'delay']);

        Route::post('/hr/nota/{date}/approve-all', [RequestController::class, 'approveAll']);
        Route::post('/hr/nota/{date}/reject-all', [RequestController::class, 'rejectAll']);
        Route::post('/hr/nota/{date}/delay-all', [RequestController::class, 'delayAll']);

        Route::get('/hr/daftar-belanja', [RequestController::class, 'shoppingList']);
        Route::get('/hr/daftar-belanja/export/excel', [RequestController::class, 'exportShoppingListExcel']);
        Route::post('/hr/daftar-belanja/complete', [RequestController::class, 'completeShopping']);
        Route::post('/hr/daftar-belanja/{request}/complete', [RequestController::class, 'completeRequest']);

        Route::get('/hr/history', [RequestController::class, 'hrHistory']);
        Route::get('/hr/history/export/pdf', [RequestController::class, 'exportHrHistoryPdf']);
        Route::get('/hr/history/export/excel', [RequestController::class, 'exportHrHistoryExcel']);
    });

    Route::middleware('admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::post('/admin/items', [AdminController::class, 'storeItem']);
        Route::post('/admin/items/import', [AdminController::class, 'importItems']);
        Route::put('/admin/items/{item}', [AdminController::class, 'updateItem']);
        Route::delete('/admin/items/{item}', [AdminController::class, 'destroyItem']);
        Route::post('/admin/users', [AdminController::class, 'storeUser']);
        Route::post('/admin/users/{user}/reset-password', [AdminController::class, 'resetUserPassword']);
        Route::post('/admin/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::post('/admin/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser']);
        Route::post('/admin/users/{user}/login-as', [AdminController::class, 'loginAs']);
        Route::post('/admin/settings/dev-mode/toggle', [AdminController::class, 'toggleDevMode']);
        Route::post('/admin/backups', [AdminController::class, 'createBackup']);
        Route::get('/admin/backups/{file}/download', [AdminController::class, 'downloadBackup']);
        Route::post('/admin/backups/{file}/restore', [AdminController::class, 'restoreBackup']);
        Route::delete('/admin/backups/{file}', [AdminController::class, 'deleteBackup']);
    });
});