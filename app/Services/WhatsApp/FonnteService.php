<?php

namespace App\Services\WhatsApp;

use App\Interfaces\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService implements WhatsAppServiceInterface
{
    public function sendText(string $phone, string $message): bool
    {
        try {
            $token = config('whatsapp.api_key');

            if (empty($token)) {
                Log::warning('[WA] Token Fonnte kosong — pengiriman dilewati.');
                return false;
            }

            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(15)
                ->post(config('whatsapp.fonnte_url'), [
                    'target'  => $this->normalize($phone),
                    'message' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('[WA] HTTP gagal', ['status' => $response->status()]);
                return false;
            }

            $data = $response->json();

            // Fonnte membalas {"status": true/false, ...}
            if (($data['status'] ?? false) !== true) {
                Log::warning('[WA] Fonnte menolak', ['body' => $data]);
                return false;
            }

            Log::info('[WA] Terkirim', ['target' => $this->normalize($phone)]);
            return true;
        } catch (\Throwable $e) {
            // best-effort: telan error, jangan ganggu transaksi pemanggil
            Log::error('[WA] Exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** Normalisasi nomor: 08xxxx -> 628xxxx */
    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }
}
