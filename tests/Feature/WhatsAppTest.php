<?php

namespace Tests\Feature;

use App\Interfaces\WhatsAppServiceInterface;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    public function test_token_kosong_return_false(): void
    {
        config(['whatsapp.api_key' => null]);
        Http::fake();

        $this->assertFalse(app(WhatsAppServiceInterface::class)->sendText('081234567890', 'Halo'));
        Http::assertNothingSent();
    }

    public function test_kirim_berhasil_return_true(): void
    {
        config(['whatsapp.api_key' => 'dummy-token']);
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        $this->assertTrue(app(WhatsAppServiceInterface::class)->sendText('081234567890', 'Halo'));

        // nomor dinormalisasi ke 62...
        Http::assertSent(fn($req) => str_contains($req->body(), '6281234567890'));
    }

    public function test_http_gagal_return_false(): void
    {
        config(['whatsapp.api_key' => 'dummy-token']);
        Http::fake(['*' => Http::response('error', 500)]);

        $this->assertFalse(app(WhatsAppServiceInterface::class)->sendText('0812', 'Halo'));
    }

    public function test_status_false_return_false(): void
    {
        config(['whatsapp.api_key' => 'dummy-token']);
        Http::fake(['*' => Http::response(['status' => false, 'reason' => 'disconnected'], 200)]);

        $this->assertFalse(app(WhatsAppServiceInterface::class)->sendText('0812', 'Halo'));
    }

    public function test_job_tidak_mengirim_saat_wa_disabled(): void
    {
        config(['whatsapp.enabled' => false]);
        Http::fake();

        (new SendWhatsAppNotification('0812', 'tes'))->handle(app(WhatsAppServiceInterface::class));

        Http::assertNothingSent();
    }
}
