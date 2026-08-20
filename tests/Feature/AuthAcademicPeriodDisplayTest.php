<?php

namespace Tests\Feature;

use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Support\ViewErrorBag;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthAcademicPeriodDisplayTest extends TestCase
{
    public function test_student_and_lecturer_logins_show_the_published_academic_period(): void
    {
        $period = $this->period();
        $this->mock(AcademicPeriodContext::class, function (MockInterface $mock) use ($period): void {
            $mock->shouldReceive('published')->twice()->andReturn($period);
        });

        foreach (['base.auth.auth-mhs-signin', 'base.auth.auth-dsn-signin'] as $view) {
            $html = $this->renderLogin($view);

            $this->assertStringContainsString('Periode Akademik', $html);
            $this->assertStringContainsString('2025/2026 Genap', $html);
            $this->assertStringContainsString('Dipublikasikan', $html);
        }
    }

    public function test_staff_login_shows_the_internal_active_academic_period(): void
    {
        $period = $this->period();
        $this->mock(AcademicPeriodContext::class, function (MockInterface $mock) use ($period): void {
            $mock->shouldReceive('active')->once()->andReturn($period);
        });

        $html = $this->renderLogin('base.auth.auth-admin-signin');

        $this->assertStringContainsString('2025/2026 Genap', $html);
        $this->assertStringContainsString('Aktif internal', $html);
    }

    public function test_student_login_does_not_expose_an_unpublished_period(): void
    {
        $this->mock(AcademicPeriodContext::class, function (MockInterface $mock): void {
            $mock->shouldReceive('published')->once()->andReturnNull();
        });

        $html = $this->renderLogin('base.auth.auth-mhs-signin');

        $this->assertStringContainsString('Belum tersedia', $html);
        $this->assertStringContainsString('Belum ada periode yang dibuka untuk mahasiswa.', $html);
    }

    private function renderLogin(string $view): string
    {
        return view($view, [
            'title' => 'Login Portal',
            'web' => (object) [
                'school_name' => 'Kampus Uji',
                'school_logo' => 'logo.png',
            ],
            'errors' => new ViewErrorBag,
        ])->render();
    }

    private function period(): TahunAkademik
    {
        return new TahunAkademik([
            'name' => '2025/2026 Genap',
            'year_start' => 2025,
            'year_end' => 2026,
            'term' => TahunAkademik::TERM_GENAP,
        ]);
    }
}
