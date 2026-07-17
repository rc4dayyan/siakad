<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdviceTurnstileTest extends TestCase
{
    public function test_advice_submission_requires_turnstile_verification(): void
    {
        $response = $this->from(route('root.home-advice'))->post(route('root.home-advice-store'), [
            'name' => 'Pengunjung Kampus',
            'email' => 'pengunjung@example.com',
            'subject' => 'Saran layanan',
            'desc' => 'Mohon tambahkan informasi layanan akademik.',
        ]);

        $response
            ->assertRedirect(route('root.home-advice'))
            ->assertSessionHasErrors([
                'cf-turnstile-response' => 'Silakan selesaikan verifikasi Cloudflare terlebih dahulu.',
            ]);
    }

    public function test_advice_submission_rejects_a_failed_turnstile_check(): void
    {
        config(['turnstile.turnstile_secret_key' => 'test-secret']);
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
            ]),
        ]);

        $response = $this->from(route('root.home-advice'))->post(route('root.home-advice-store'), [
            'name' => 'Pengunjung Kampus',
            'email' => 'pengunjung@example.com',
            'subject' => 'Saran layanan',
            'desc' => 'Mohon tambahkan informasi layanan akademik.',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response
            ->assertRedirect(route('root.home-advice'))
            ->assertSessionHasErrors('cf-turnstile-response');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'invalid-token');
    }
}
