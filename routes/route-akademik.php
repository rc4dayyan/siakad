<?php

use Illuminate\Support\Facades\Route;

// HAK AKSES DEPARTEMENT ACADEMIC
Route::group(['prefix' => 'academic', 'middleware' => ['user-access:Departement Academic'], 'as' => 'academic.'],function(){

    // GLOBAL ROUTE
    require __DIR__.'/route-global.php';

    // STATUS ACTIVE BOLEH AKSES INI
    Route::middleware(['is-active:1'])->group(function () {

        // MENU KHUSUS DATA PENGGUNA => DATA MAHASISWA
        Route::get('/data-mahasiswa',[App\Http\Controllers\Admin\Pages\WorkersController::class, 'indexStudent'])->name('workers.student-index');
        Route::get('/kenaikan-semester',[App\Http\Controllers\Admin\SemesterPromotionController::class, 'index'])->name('workers.student-promotion-index');
        Route::post('/kenaikan-semester/preview',[App\Http\Controllers\Admin\SemesterPromotionController::class, 'preview'])->name('workers.student-promotion-preview');
        Route::post('/kenaikan-semester/execute',[App\Http\Controllers\Admin\SemesterPromotionController::class, 'execute'])->name('workers.student-promotion-execute');
        Route::get('/data-mahasiswa/create',[App\Http\Controllers\Admin\Pages\WorkersController::class, 'createStudent'])->name('workers.student-create');
        Route::get('/data-mahasiswa/{code}/edit',[App\Http\Controllers\Admin\Pages\WorkersController::class, 'editStudent'])->name('workers.student-edit');
        Route::post('/data-mahasiswa/store',[App\Http\Controllers\Admin\Pages\WorkersController::class, 'storeStudent'])->name('workers.student-store');
        Route::post('/data-mahasiswa/{code}/registrasi',[App\Http\Controllers\Admin\StudentRegistrationController::class, 'store'])->name('workers.student-registration-store');
        Route::patch('/data-mahasiswa/{code}/update',[App\Http\Controllers\Admin\Pages\WorkersController::class, 'updateStudent'])->name('workers.student-update');
        Route::patch('/data-mahasiswa/{code}/status-akademik',[App\Http\Controllers\Admin\AcademicStatusController::class, 'update'])->name('workers.student-academic-status-update');
        Route::delete('/data-mahasiswa/{code}/destroy',[App\Http\Controllers\Admin\Pages\WorkersController::class, 'destroyStudent'])->name('workers.student-destroy');
        Route::get('/krs-management', [App\Http\Controllers\Admin\KrsManagementController::class, 'index'])->name('krs-management.index');
        Route::get('/krs-management/import/template', [App\Http\Controllers\Admin\KrsManagementController::class, 'importTemplate'])->name('krs-management.import-template');
        Route::post('/krs-management/import/preview', [App\Http\Controllers\Admin\KrsManagementController::class, 'importPreview'])->name('krs-management.import-preview');
        Route::post('/krs-management/import/execute', [App\Http\Controllers\Admin\KrsManagementController::class, 'importExecute'])->name('krs-management.import-execute');
        Route::patch('/krs-management/bulk', [App\Http\Controllers\Admin\KrsManagementController::class, 'bulk'])->name('krs-management.bulk');
        Route::post('/krs-management/{registration}/items', [App\Http\Controllers\Admin\KrsManagementController::class, 'add'])->name('krs-management.add');
        Route::post('/krs-management/{registration}/items/bulk', [App\Http\Controllers\Admin\KrsManagementController::class, 'addMany'])->name('krs-management.add-many');
        Route::delete('/krs-management/items/{item}', [App\Http\Controllers\Admin\KrsManagementController::class, 'remove'])->name('krs-management.remove');
        Route::patch('/krs-management/{krs}/submit-on-behalf', [App\Http\Controllers\Admin\KrsManagementController::class, 'submitOnBehalf'])->name('krs-management.submit-on-behalf');
        Route::patch('/krs-management/{krs}/reopen', [App\Http\Controllers\Admin\KrsManagementController::class, 'reopen'])->name('krs-management.reopen');

        // MENU KHUSUS DATA MASTER => DATA KELAS
        Route::get('/master/data-kelas',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'index'])->name('master.kelas-index');
        Route::get('/master/data-kelas/{code}/view/mahasiswa',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'viewMahasiswa'])->name('master.kelas-mahasiswa-view');
        Route::post('/master/data-kelas/{code}/mahasiswa',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'assignMahasiswa'])->name('master.kelas-mahasiswa-assign');
        Route::post('/master/data-kelas/store',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'store'])->name('master.kelas-store');
        Route::post('/master/data-kelas/{code}/cetak/mahasiswa',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'cetakMahasiswa'])->name('master.kelas-mahasiswa-cetak');
        Route::patch('/master/data-kelas/{code}/update',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'update'])->name('master.kelas-update');
        Route::delete('/master/data-kelas/{code}/destroy',[App\Http\Controllers\Admin\Pages\Core\KelasController::class, 'destroy'])->name('master.kelas-destroy');
        Route::get('/services/convert/export-kelas', [App\Http\Controllers\Services\Convert\ExportController::class, 'exportKelas'])->name('services.convert.export-kelas');
        Route::post('/services/convert/import-kelas', [App\Http\Controllers\Services\Convert\ImportController::class, 'importKelas'])->name('services.convert.import-kelas');

        // MENU KHUSUS DATA MASTER => DATA KURIKULUM
        Route::get('/master/data-kurikulum',[App\Http\Controllers\Admin\Pages\Core\KurikulumController::class, 'index'])->name('master.kurikulum-index');
        Route::get('/master/data-kurikulum/{code}/view/',[App\Http\Controllers\Admin\Pages\Core\KurikulumController::class, 'view'])->name('master.kurikulum-view');
        Route::post('/master/data-kurikulum/store',[App\Http\Controllers\Admin\Pages\Core\KurikulumController::class, 'store'])->name('master.kurikulum-store');
        Route::patch('/master/data-kurikulum/{code}/update',[App\Http\Controllers\Admin\Pages\Core\KurikulumController::class, 'update'])->name('master.kurikulum-update');
        Route::delete('/master/data-kurikulum/{code}/destroy',[App\Http\Controllers\Admin\Pages\Core\KurikulumController::class, 'destroy'])->name('master.kurikulum-destroy');

        // MENU KHUSUS DATA MASTER => DATA MATAKULIAH
        Route::get('/master/data-matkul',[App\Http\Controllers\Admin\Pages\Core\MataKuliahController::class, 'index'])->name('master.matkul-index');
        Route::get('/master/data-matkul/create',[App\Http\Controllers\Admin\Pages\Core\MataKuliahController::class, 'create'])->name('master.matkul-create');
        Route::post('/master/data-matkul/store',[App\Http\Controllers\Admin\Pages\Core\MataKuliahController::class, 'store'])->name('master.matkul-store');
        Route::patch('/master/data-matkul/{code}/update',[App\Http\Controllers\Admin\Pages\Core\MataKuliahController::class, 'update'])->name('master.matkul-update');
        Route::delete('/master/data-matkul/{code}/destroy',[App\Http\Controllers\Admin\Pages\Core\MataKuliahController::class, 'destroy'])->name('master.matkul-destroy');
        Route::get('/master/penawaran-matkul', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'index'])->name('master.penawaran-index');
        Route::post('/master/penawaran-matkul', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'store'])->name('master.penawaran-store');
        Route::get('/master/penawaran-matkul/export', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'export'])->name('master.penawaran-export');
        Route::post('/master/penawaran-matkul/import', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'import'])->name('master.penawaran-import');
        Route::patch('/master/penawaran-matkul/jadwal-krs', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'updateKrsWindow'])->name('master.penawaran-krs-window');
        Route::patch('/master/penawaran-matkul/{penawaran}', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'update'])->name('master.penawaran-update');
        Route::delete('/master/penawaran-matkul/{penawaran}', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'destroy'])->name('master.penawaran-destroy');
        Route::post('/master/penawaran-matkul/salin/pratinjau', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'copyPreview'])->name('master.penawaran-copy-preview');
        Route::post('/master/penawaran-matkul/salin/eksekusi', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'copyExecute'])->name('master.penawaran-copy-execute');
        Route::get('/master/penawaran-matkul/{penawaran}/peserta', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'participants'])->name('master.penawaran-participants');
        Route::get('/master/penawaran-matkul/{penawaran}/nilai', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'grades'])->name('master.penawaran-grades');
        Route::post('/master/penawaran-matkul/{penawaran}/nilai', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'storeGrades'])->name('master.penawaran-grades-store');
        Route::get('/master/penawaran-matkul/{penawaran}/nilai/export', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'exportGrades'])->name('master.penawaran-grades-export');
        Route::post('/master/penawaran-matkul/{penawaran}/nilai/import', [App\Http\Controllers\Admin\PenawaranMataKuliahController::class, 'importGrades'])->name('master.penawaran-grades-import');
        Route::get('/services/convert/export-matkul', [App\Http\Controllers\Services\Convert\ExportController::class, 'exportMataKuliah'])->name('services.convert.export-matkul');

        // MENU KHUSUS DATA MASTER => DATA JADWAL KULIAH
        Route::get('/master/data-jadkul',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'index'])->name('master.jadkul-index');
        Route::get('/master/data-jadkul/{code}/viewAbsen',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'viewAbsen'])->name('master.jadkul-absen-view');
        Route::get('/master/data-jadkul/create',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'create'])->name('master.jadkul-create');
        Route::post('/master/data-jadkul/store',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'store'])->name('master.jadkul-store');
        Route::post('/master/data-jadkul/{code}/cetakAbsen',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'cetakAbsen'])->name('master.jadkul-absen-cetak');
        Route::patch('/master/data-jadkul/{code}/updateAbsen',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'updateAbsen'])->name('master.jadkul-absen-update');
        Route::patch('/master/data-jadkul/{code}/update',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'update'])->name('master.jadkul-update');
        Route::delete('/master/data-jadkul/{code}/destroy',[App\Http\Controllers\Admin\Pages\Core\JadwalKuliahController::class, 'destroy'])->name('master.jadkul-destroy');
        Route::get('/services/convert/export-jadkul', [App\Http\Controllers\Services\Convert\ExportController::class, 'exportJadwalKuliah'])->name('services.convert.export-jadkul');
        Route::post('/services/convert/import-jadkul', [App\Http\Controllers\Services\Convert\ImportController::class, 'importJadwalKuliah'])->name('services.convert.import-jadkul');
        Route::get('/master/jadwal-mingguan', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'index'])->name('master.jadwal-mingguan-index');
        Route::get('/master/jadwal-mingguan/cetak', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'printTimetable'])->name('master.jadwal-mingguan-print');
        Route::get('/master/jadwal-mingguan/rekap-presensi', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'recap'])->name('master.jadwal-mingguan-recap');
        Route::get('/master/jadwal-mingguan/export', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'export'])->name('master.jadwal-mingguan-export');
        Route::post('/master/jadwal-mingguan/import', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'import'])->name('master.jadwal-mingguan-import');
        Route::post('/master/jadwal-mingguan', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'store'])->name('master.jadwal-mingguan-store');
        Route::post('/master/jadwal-mingguan/kalender-libur', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'storeHoliday'])->name('master.jadwal-mingguan-holiday-store');
        Route::patch('/master/jadwal-mingguan/{jadwal}', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'update'])->name('master.jadwal-mingguan-update');
        Route::delete('/master/jadwal-mingguan/{jadwal}', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'destroy'])->name('master.jadwal-mingguan-destroy');
        Route::get('/master/jadwal-mingguan/{jadwal}/pertemuan/pratinjau', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'preview'])->name('master.jadwal-mingguan-preview');
        Route::post('/master/jadwal-mingguan/{jadwal}/pertemuan/generate', [App\Http\Controllers\Admin\JadwalMingguanController::class, 'generate'])->name('master.jadwal-mingguan-generate');

        // SERVICE CONVERT EXPORT - IMPORT
        Route::get('/services/convert/export-student',[App\Http\Controllers\Services\Convert\ExportController::class, 'exportStudent'])->name('services.convert.export-student');
        Route::get('/services/convert/export-users',[App\Http\Controllers\Services\Convert\ExportController::class, 'exportUsers'])->name('services.convert.export-users');
        Route::post('/services/convert/import-users',[App\Http\Controllers\Services\Convert\ImportController::class, 'importUsers'])->name('services.convert.import-users');
        Route::post('/services/convert/import-student',[App\Http\Controllers\Services\Convert\ImportController::class, 'importStudent'])->name('services.convert.import-student');
        Route::post('/services/convert/import-matkul', [App\Http\Controllers\Services\Convert\ImportController::class, 'importMataKuliah'])->name('services.convert.import-matkul');


    });

});
