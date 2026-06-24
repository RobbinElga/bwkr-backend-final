<?php

namespace App\Interfaces;

interface WhatsAppServiceInterface
{
    /**
     * Kirim pesan teks WhatsApp.
     * BEST-EFFORT: kembalikan true/false — JANGAN PERNAH melempar exception.
     */
    public function sendText(string $phone, string $message): bool;
}
