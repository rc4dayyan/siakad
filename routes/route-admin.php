<?php

use Illuminate\Support\Facades\Route;

// HAK AKSES DEPARTEMENT ACADEMIC
Route::group(['prefix' => 'admin', 'middleware' => ['user-access:Departement Admin'], 'as' => 'admin.'],function(){

    // GLOBAL ROUTE
    require __DIR__.'/route-global.php';

    // STATUS ACTIVE BOLEH AKSES INI
    Route::middleware(['is-active:1'])->group(function () {
        Route::get('/krs-approval', [App\Http\Controllers\Admin\KrsManagementController::class, 'index'])->name('krs-management.index');
        Route::get('/krs-approval/import/template', [App\Http\Controllers\Admin\KrsManagementController::class, 'importTemplate'])->name('krs-management.import-template');
        Route::post('/krs-approval/import/preview', [App\Http\Controllers\Admin\KrsManagementController::class, 'importPreview'])->name('krs-management.import-preview');
        Route::post('/krs-approval/import/execute', [App\Http\Controllers\Admin\KrsManagementController::class, 'importExecute'])->name('krs-management.import-execute');
        Route::patch('/krs-approval/bulk', [App\Http\Controllers\Admin\KrsManagementController::class, 'bulk'])->name('krs-management.bulk');
        Route::patch('/krs-approval/{krs}/approve', [App\Http\Controllers\Admin\KrsManagementController::class, 'approve'])->name('krs-management.approve');
    });

});
