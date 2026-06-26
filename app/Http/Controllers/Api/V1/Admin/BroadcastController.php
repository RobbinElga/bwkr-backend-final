<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BroadcastRequest;
use App\Jobs\SendWhatsAppNotification;
use App\Models\BroadcastTemplate;
use App\Models\CrmLog;
use App\Services\AuditService;
use App\Services\CrmService;
use Illuminate\Support\Facades\Auth;
use App\Models\Broadcast;
use App\Enums\BroadcastStatus;
use App\Http\Resources\BroadcastResource;

class BroadcastController extends Controller
{
    public function __construct(
        private readonly CrmService $crm,
        private readonly AuditService $audit,
    ) {}

    /** POST /admin/crm/broadcast — kirim WA massal ke donatur (placeholder per-orang). */
    /** POST /admin/crm/broadcast — simpan riwayat + kirim WA massal langsung. */
    public function send(BroadcastRequest $request)
    {
        $data = $request->validated();

        $template = ! empty($data['template_id'])
            ? BroadcastTemplate::findOrFail($data['template_id'])->content
            : ($data['message'] ?? null);

        abort_if(empty($template), 422, 'Pesan atau template wajib diisi.');

        $recipients = $this->crm->resolveRecipients($data['phone_hashes'] ?? null, $data['tier'] ?? null);

        if ($recipients->isEmpty()) {
            return response()->json(['message' => 'Tidak ada penerima yang cocok.', 'sent' => 0], 422);
        }

        // Simpan riwayat broadcast
        $broadcast = Broadcast::create([
            'title'           => $data['title'],
            'message'         => $data['message'] ?? null,
            'template_id'     => $data['template_id'] ?? null,
            'tier'            => $data['tier'] ?? null,
            'status'          => BroadcastStatus::Sent,
            'sent_at'         => now(),
            'recipient_count' => $recipients->count(),
            'created_by'      => Auth::id(),
        ]);

        $interval = (int) config('whatsapp.broadcast_interval', 6); // detik antar pesan → 5 pesan / 30 detik

        foreach ($recipients->values() as $i => $r) {
            $message = $this->crm->fillPlaceholders($template, $r);

            SendWhatsAppNotification::dispatch($r['phone'], $message)
                ->onConnection('database')                       // paksa lewat queue (bukan sync)
                ->delay(now()->addSeconds($i * $interval));      // jeda bertahap

            CrmLog::create([
                'donor_phone_hash' => $r['hash'],
                'contacted_by'     => Auth::id(),
                'channel'          => 'whatsapp',
                'template_id'      => $data['template_id'] ?? null,
                'message'          => $message,
                'status'           => 'sent',
            ]);
        }

        $this->audit->log('broadcast', $broadcast, new: [
            'title'       => $broadcast->title,
            'count'       => $recipients->count(),
            'template_id' => $data['template_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Broadcast diproses.',
            'sent'    => $recipients->count(),
        ]);
    }

    /** GET /admin/crm/broadcasts — riwayat broadcast. */
    public function index()
    {
        $broadcasts = Broadcast::with('template')
            ->latest()
            ->paginate(15);

        return BroadcastResource::collection($broadcasts);
    }
}
