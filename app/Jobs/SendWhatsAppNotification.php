<?php

namespace App\Jobs;

use App\Interfaces\WhatsAppServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $phone,
        public string $message,
    ) {}

    public function handle(WhatsAppServiceInterface $wa): void
    {
        if (! config('whatsapp.enabled')) {
            return;
        }

        // service sudah menangani error & log; di sini cukup panggil
        $wa->sendText($this->phone, $this->message);
    }
}
