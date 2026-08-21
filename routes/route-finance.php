<?php

use Illuminate\Support\Facades\Route;

// HAK AKSES DEPARTEMENT FINANCE
Route::group(['prefix' => 'finance', 'middleware' => ['user-access:Departement Finance'], 'as' => 'finance.'],function(){

    // GLOBAL ROUTE
    require __DIR__.'/route-global.php';

    // STATUS ACTIVE BOLEH AKSES INI
    Route::middleware(['is-active:1'])->group(function () {



        // MENU KHUSUS FINANCE DEPARTEMENT => DATA TAGIHAN
        Route::get('/data-tagihan',[App\Http\Controllers\Admin\Pages\Finance\GenerateTagihanController::class, 'index'])->name('finance.tagihan-index');
        Route::get('/data-tagihan/create',[App\Http\Controllers\Admin\Pages\Finance\GenerateTagihanController::class, 'create'])->name('finance.tagihan-create');
        Route::post('/data-tagihan/store',[App\Http\Controllers\Admin\Pages\Finance\GenerateTagihanController::class, 'store'])->name('finance.tagihan-store');
        Route::post('/data-tagihan/{code}/prepare',[App\Http\Controllers\Admin\Pages\Finance\GenerateTagihanController::class, 'prepare'])->name('finance.tagihan-prepare');
        Route::patch('/data-tagihan/{code}/update',[App\Http\Controllers\Admin\Pages\Finance\GenerateTagihanController::class, 'update'])->name('finance.tagihan-update');
        Route::delete('/data-tagihan/{code}/destroy',[App\Http\Controllers\Admin\Pages\Finance\GenerateTagihanController::class, 'destroy'])->name('finance.tagihan-destroy');
        Route::get('/billing-period', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'index'])->name('billing-period.index');
        Route::post('/billing-period/templates', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'store'])->name('billing-period.store');
        Route::patch('/billing-period/templates/{template}', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'update'])->name('billing-period.update');
        Route::get('/billing-period/templates/{template}/preview', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'preview'])->name('billing-period.preview');
        Route::post('/billing-period/templates/{template}/issue', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'issue'])->name('billing-period.issue');
        Route::delete('/billing-period/templates/{template}', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'destroy'])->name('billing-period.destroy');
        Route::post('/billing-period/override/{registration}', [App\Http\Controllers\Admin\Pages\Finance\PeriodBillingController::class, 'override'])->name('billing-period.override');
        // MENU KHUSUS FINANCE DEPARTEMENT => DATA PEMBAYARAN
        Route::get('/data-pembayaran',[App\Http\Controllers\Admin\Pages\Finance\PembayaranController::class, 'index'])->name('finance.pembayaran-index');
        Route::get('/data-pembayaran/{payment}/bukti',[App\Http\Controllers\Admin\Pages\Finance\PembayaranController::class, 'proof'])->name('finance.pembayaran-proof');
        Route::patch('/data-pembayaran/{payment}/decision',[App\Http\Controllers\Admin\Pages\Finance\PembayaranController::class, 'decision'])->name('finance.pembayaran-decision');
        // MENU KHUSUS FINANCE DEPARTEMENT => DATA KEUANGAN
        Route::get('/data-keuangan',[App\Http\Controllers\Admin\Pages\Finance\BalanceController::class, 'index'])->name('finance.keuangan-index');
        Route::post('/data-keuangan/store',[App\Http\Controllers\Admin\Pages\Finance\BalanceController::class, 'store'])->name('finance.keuangan-store');
        Route::patch('/data-keuangan/{code}/update',[App\Http\Controllers\Admin\Pages\Finance\BalanceController::class, 'update'])->name('finance.keuangan-update');
        Route::delete('/data-keuangan/{code}/destroy',[App\Http\Controllers\Admin\Pages\Finance\BalanceController::class, 'destroy'])->name('finance.keuangan-destroy');

        // MENU KHUSUS FINANCE DEPARTEMENT => DATA APPROVAL ABSENSI KARYAWAN
        Route::get('/approval-absen',[App\Http\Controllers\Admin\Pages\Finance\ApprovalController::class, 'indexAbsen'])->name('approval.absen-index');
        Route::get('/approval-absen/approved',[App\Http\Controllers\Admin\Pages\Finance\ApprovalController::class, 'indexAbsenApproved'])->name('approval.absen-index-approved');
        Route::get('/approval-absen/rejected',[App\Http\Controllers\Admin\Pages\Finance\ApprovalController::class, 'indexAbsenRejected'])->name('approval.absen-index-rejected');
        Route::patch('/approval-absen/{code}/update/accept',[App\Http\Controllers\Admin\Pages\Finance\ApprovalController::class, 'updateAbsenAccept'])->name('approval.absen-update-accept');
        Route::patch('/approval-absen/{code}/update/reject',[App\Http\Controllers\Admin\Pages\Finance\ApprovalController::class, 'updateAbsenReject'])->name('approval.absen-update-reject');

    });

});
