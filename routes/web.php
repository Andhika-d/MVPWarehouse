<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\HelpGuideController;
use App\Http\Controllers\AdminHelpGuideController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProcurementNoteController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

//middleware group for authenticated users
Route::middleware('auth')->group(function () {
    Route::get('/ubah-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/ubah-password', [AuthController::class, 'changePassword']);

    Route::get('/notifications/poll', [NotificationController::class, 'poll']);
    Route::get('/bantuan', [HelpGuideController::class, 'index'])->name('help.index');
    Route::get('/bantuan/{guide:slug}', [HelpGuideController::class, 'show'])->name('help.show');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/admin/impersonation/stop', [AdminController::class, 'stopImpersonation']);

    Route::middleware('role:gudang')->group(function () {
        Route::get('/gudang/dashboard', [DashboardController::class, 'gudangDashboard']);
        Route::get('/gudang/stock', [DashboardController::class, 'gudangStock']);

        Route::get('/gudang/penerimaan', [GudangController::class, 'penerimaanIndex']);
        Route::post('/gudang/penerimaan', [GudangController::class, 'penerimaanStore'])->middleware('throttle:20,1');
        Route::post('/gudang/penerimaan/close', [GudangController::class, 'closeSisaStore'])->middleware('throttle:20,1');

        Route::get('/gudang/barang-keluar', [GudangController::class, 'barangKeluarIndex']);
        Route::post('/gudang/barang-keluar', [GudangController::class, 'barangKeluarStore'])->middleware('throttle:20,1');

        Route::get('/gudang/movements', [GudangController::class, 'movementsIndex']);
        Route::get('/gudang/movements/export/preview', [GudangController::class, 'previewMovementExport']);
        Route::get('/gudang/movements/export/excel', [GudangController::class, 'exportMovementExcel']);

        Route::get('/gudang/request-barang', [RequestController::class, 'create']);
        Route::post('/gudang/request-barang', [RequestController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/gudang/history', [RequestController::class, 'history']);
        Route::get('/gudang/history/export/preview', [RequestController::class, 'previewHistoryExport']);
        Route::get('/gudang/history/export/pdf', [RequestController::class, 'exportHistoryPdf']);
        Route::get('/gudang/history/export/excel', [RequestController::class, 'exportHistoryExcel']);
        Route::get('/gudang/history/{request}', [RequestController::class, 'detail']);

        Route::get('/gudang/location-change/search', [GudangController::class, 'locationSearch']);
        Route::get('/gudang/location-change/export/preview', [GudangController::class, 'previewLocationChangesExport']);
        Route::get('/gudang/location-change/export/excel', [GudangController::class, 'exportLocationChangesExcel']);
        Route::get('/gudang/location-change/export/pdf', [GudangController::class, 'exportLocationChangesPdf']);
        Route::get('/gudang/location-change', [GudangController::class, 'locationChangeIndex']);
        Route::post('/gudang/location-change', [GudangController::class, 'locationChangeStore'])->middleware('throttle:20,1');
    });

    Route::middleware('role:hr')->group(function () {
        Route::get('/hr/dashboard', [DashboardController::class, 'hrDashboard']);
        Route::get('/hr/stock', [DashboardController::class, 'hrStock']);
        Route::get('/hr/approval', [RequestController::class, 'approvalIndex']);
        Route::get('/hr/approval/export/preview', [RequestController::class, 'previewApprovalExport']);
        Route::get('/hr/approval/export/pdf', [RequestController::class, 'exportApprovalPdf']);
        Route::get('/hr/approval/export/excel', [RequestController::class, 'exportApprovalExcel']);
        Route::get('/hr/requests/{request}', [RequestController::class, 'approvalDetail']);
        Route::post('/hr/requests/{request}/approve', [RequestController::class, 'approve']);
        Route::post('/hr/requests/{request}/reject', [RequestController::class, 'reject']);
        Route::post('/hr/requests/{request}/delay', [RequestController::class, 'delay']);
        Route::post('/hr/requests/{request}/close', [RequestController::class, 'hrCloseSisa'])->middleware('throttle:20,1')->name('hr.requests.close');

        Route::post('/hr/nota/{date}/approve-all', [RequestController::class, 'approveAll']);
        Route::post('/hr/nota/{date}/reject-all', [RequestController::class, 'rejectAll']);
        Route::post('/hr/nota/{date}/delay-all', [RequestController::class, 'delayAll']);

        Route::get('/hr/daftar-belanja', [RequestController::class, 'shoppingList']);
        Route::get('/hr/daftar-belanja/export/excel', [RequestController::class, 'exportShoppingListExcel']);
        Route::post('/hr/nota-pengadaan', [ProcurementNoteController::class, 'store'])->name('hr.procurement-notes.store');
        Route::get('/hr/nota-pengadaan/{procurementNote}', [ProcurementNoteController::class, 'show'])->name('hr.procurement-notes.show');
        Route::put('/hr/nota-pengadaan/{procurementNote}', [ProcurementNoteController::class, 'update'])->name('hr.procurement-notes.update');
        Route::post('/hr/nota-pengadaan/{procurementNote}/issue', [ProcurementNoteController::class, 'issue'])->name('hr.procurement-notes.issue');
        Route::post('/hr/nota-pengadaan/{procurementNote}/cancel', [ProcurementNoteController::class, 'cancel'])->name('hr.procurement-notes.cancel');
        Route::get('/hr/nota-pengadaan/{procurementNote}/print', [ProcurementNoteController::class, 'print'])->name('hr.procurement-notes.print');
        Route::get('/hr/nota-pengadaan/{procurementNote}/excel', [ProcurementNoteController::class, 'excel'])->name('hr.procurement-notes.excel');

        Route::get('/hr/history', [RequestController::class, 'hrHistory']);
        Route::get('/hr/history/export/preview', [RequestController::class, 'previewHrHistoryExport']);
        Route::get('/hr/history/export/pdf', [RequestController::class, 'exportHrHistoryPdf']);
        Route::get('/hr/history/export/excel', [RequestController::class, 'exportHrHistoryExcel']);
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/help-guides', [AdminHelpGuideController::class, 'index'])->name('help-guides.index');
        Route::get('/help-guides/create', [AdminHelpGuideController::class, 'create'])->name('help-guides.create');
        Route::post('/help-guides', [AdminHelpGuideController::class, 'store'])->name('help-guides.store');
        Route::get('/help-guides/{helpGuide}/edit', [AdminHelpGuideController::class, 'edit'])->name('help-guides.edit');
        Route::put('/help-guides/{helpGuide}', [AdminHelpGuideController::class, 'update'])->name('help-guides.update');
        Route::post('/help-guides/{helpGuide}/publish', [AdminHelpGuideController::class, 'publish'])->name('help-guides.publish');
        Route::post('/help-guides/{helpGuide}/archive', [AdminHelpGuideController::class, 'archive'])->name('help-guides.archive');
        Route::delete('/help-guides/{helpGuide}', [AdminHelpGuideController::class, 'destroy'])->name('help-guides.destroy');
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // Master Items
        Route::get('/items', [AdminController::class, 'itemsIndex'])->name('items.index');
        Route::post('/items', [AdminController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{item}', [AdminController::class, 'updateItem'])->name('items.update');
        Route::post('/items/{item}/adjust-stock', [AdminController::class, 'adjustStock'])->name('items.adjust-stock');
        Route::delete('/items/{item}', [AdminController::class, 'destroyItem'])->name('items.destroy');

        // Import
        Route::get('/import', [AdminController::class, 'importIndex'])->name('import.index');
        Route::post('/import/preview', [AdminController::class, 'importPreview'])->name('import.preview');
        Route::post('/import/execute', [AdminController::class, 'importExecute'])->name('import.execute');

        // Storage Locations
        Route::get('/locations', [AdminController::class, 'locationsIndex'])->name('locations.index');

        // Location Change Requests
        Route::get('/location-changes', [AdminController::class, 'locationChangesIndex'])->name('location-changes.index');
        Route::get('/location-changes/export/preview', [AdminController::class, 'previewLocationChangesExport'])->name('location-changes.export-preview');
        Route::get('/location-changes/export/excel', [AdminController::class, 'exportLocationChangesExcel'])->name('location-changes.export-excel');
        Route::get('/location-changes/export/pdf', [AdminController::class, 'exportLocationChangesPdf'])->name('location-changes.export-pdf');
        Route::post('/location-changes/{change}/approve', [AdminController::class, 'approveLocationChange'])->name('location-changes.approve');
        Route::post('/location-changes/{change}/reject', [AdminController::class, 'rejectLocationChange'])->name('location-changes.reject');

        // Users
        Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::post('/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('users.reset-password');
        Route::post('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.destroy');
        Route::post('/users/{user}/login-as', [AdminController::class, 'loginAs'])->name('users.login-as');

        // Audit Log
        Route::get('/audit', [AdminController::class, 'auditIndex'])->name('audit.index');
        Route::get('/audit/export/preview', [AdminController::class, 'previewAuditExport'])->name('audit.export-preview');
        Route::get('/audit/export/excel', [AdminController::class, 'exportAuditExcel'])->name('audit.export-excel');

        // Developer Mode
        Route::post('/settings/dev-mode/toggle', [AdminController::class, 'toggleDevMode'])->name('settings.dev-mode.toggle');

        // Backup
        Route::get('/backups', [AdminController::class, 'backupIndex'])->name('backups.index');
        Route::post('/backups', [AdminController::class, 'createBackup'])->name('backups.create');
        Route::get('/backups/{file}/download', [AdminController::class, 'downloadBackup'])->name('backups.download');
        Route::post('/backups/{file}/restore', [AdminController::class, 'restoreBackup'])->name('backups.restore');
        Route::delete('/backups/{file}', [AdminController::class, 'deleteBackup'])->name('backups.destroy');

        // Reset Maintenance
        Route::get('/reset', [AdminController::class, 'resetIndex'])->name('reset.index');
        Route::post('/reset/master-items', [AdminController::class, 'resetMasterItems'])->name('reset.master-items');
        Route::post('/reset/stock-movements', [AdminController::class, 'resetStockMovements'])->name('reset.stock-movements');
        Route::post('/reset/stock-requests', [AdminController::class, 'resetStockRequests'])->name('reset.stock-requests');
        Route::post('/reset/location-changes', [AdminController::class, 'resetLocationChanges'])->name('reset.location-changes');
    });

    Route::middleware('role:director')->prefix('director')->name('director.')->group(function () {
        Route::get('/dashboard', [DirectorController::class, 'dashboard'])->name('dashboard');
        Route::get('/requests', [DirectorController::class, 'requests'])->name('requests');
        Route::get('/requests/{request}', [DirectorController::class, 'requestDetail'])->name('request-detail');
        Route::get('/movements', [DirectorController::class, 'movements'])->name('movements');
        Route::get('/timeline', [DirectorController::class, 'timeline'])->name('timeline');
        Route::get('/stock', [DirectorController::class, 'stock'])->name('stock');
        Route::get('/issues', [DirectorController::class, 'issues'])->name('issues');
    });
});
