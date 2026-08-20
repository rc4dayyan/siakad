<?php

namespace App\Providers;

use App\Services\Academic\AcademicPeriodContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');

        View::composer('base.panel.base-panel-header', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $context = app(AcademicPeriodContext::class);
            $user = auth()->user();

            $view->with([
                'academicPeriods' => $context->availableFor($user),
                'selectedAcademicPeriod' => $context->current($user),
                'activeAcademicPeriod' => $context->active(),
            ]);
        });

        View::composer([
            'base.auth.auth-mhs-signin',
            'base.auth.auth-dsn-signin',
        ], function ($view): void {
            $view->with('academicPeriod', app(AcademicPeriodContext::class)->published());
        });

        View::composer('base.auth.auth-admin-signin', function ($view): void {
            $view->with('academicPeriod', app(AcademicPeriodContext::class)->active());
        });
    }
}
