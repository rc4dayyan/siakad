<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class MaintenanceController extends Controller
{
    public function clearCache(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('optimize:clear');

            if ($exitCode !== 0) {
                Alert::error('Error', 'Cache aplikasi gagal dibersihkan.');

                return back();
            }

            Alert::success('Success', 'Cache aplikasi berhasil dibersihkan.');
        } catch (Throwable $exception) {
            Log::error('Failed to clear application cache.', [
                'exception' => $exception,
            ]);

            Alert::error('Error', 'Cache aplikasi gagal dibersihkan.');
        }

        return back();
    }
}
