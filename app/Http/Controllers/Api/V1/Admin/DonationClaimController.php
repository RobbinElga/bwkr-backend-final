<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ClaimStatus;
use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\RejectClaimRequest;
use App\Http\Requests\Claim\StoreClaimRequest;
use App\Http\Resources\ClaimResource;
use App\Jobs\SendWhatsAppNotification;
use App\Models\DonationClaim;
use App\Models\DonationInput;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Services\ReportExporter;
use App\Support\ReportPeriod;

class DonationClaimController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notify,
    ) {}

    public function index(Request $request)
    {
        $claims = DonationClaim::with(['project', 'donationInput'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->project_id, fn($q, $id) => $q->where('project_id', $id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ClaimResource::collection($claims);
    }

    public function store(StoreClaimRequest $request)
    {
        $claim = DB::transaction(function () use ($request) {
            // kunci baris donasi agar tidak ada alokasi ganda saat bersamaan
            $input = DonationInput::lockForUpdate()->findOrFail($request->donation_input_id);

            // dana yang sudah dialokasikan (pending + approved) ikut "mengunci" nominal
            $allocated = DonationClaim::where('donation_input_id', $input->id)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('amount');

            $remaining = $input->amount - $allocated;

            abort_if(
                $request->amount > $remaining,
                422,
                'Nominal melebihi sisa yang belum dialokasikan (Rp' . number_format($remaining, 0, ',', '.') . ').'
            );

            $claim = DonationClaim::create([
                'donation_input_id' => $input->id,
                'project_id'        => $request->project_id,
                'claimed_by'        => Auth::id(),
                'amount'            => $request->amount,
                'notes'             => $request->notes,
                'status'            => ClaimStatus::Pending->value,
            ]);

            $this->audit->log('created', $claim, new: $claim->toArray());

            return $claim;
        });

        $this->notify->notifyRoles(
            ['super_admin', 'admin'],
            'claim.pending',
            'Klaim donasi menunggu persetujuan',
            'Rp' . number_format($claim->amount, 0, ',', '.') . ' menunggu diverifikasi.',
            '/keuangan/claim'
        );

        return (new ClaimResource($claim->load('project', 'donationInput')))
            ->response()->setStatusCode(201);
    }

    public function approve(DonationClaim $claim)
    {
        if ($claim->status !== ClaimStatus::Pending) {
            return response()->json(['message' => 'Klaim ini sudah diproses.'], 422);
        }

        DB::transaction(function () use ($claim) {
            $claim->update([
                'status'      => ClaimStatus::Approved->value,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // tandai donasi "claimed" bila sudah teralokasi penuh
            $input = $claim->donationInput;
            $approvedTotal = DonationClaim::where('donation_input_id', $input->id)
                ->where('status', ClaimStatus::Approved->value)
                ->sum('amount');

            if ($approvedTotal >= $input->amount) {
                $input->update(['status' => DonationStatus::Claimed->value]);
            }

            $this->audit->log('approved', $claim, new: [
                'status' => 'approved',
                'approved_by' => Auth::id(),
            ]);
        });

        // setelah transaksi sukses: WA sukses ke donatur (best-effort)
        $claim->refresh()->load('project', 'donationInput');
        SendWhatsAppNotification::dispatch(
            $claim->donationInput->donor_phone,
            $this->approvedMessage($claim)
        );

        if ($claim->claimed_by !== Auth::id()) {
            $this->notify->notifyUser(
                $claim->claimed_by,
                'claim.approved',
                'Klaim donasi disetujui',
                'Rp' . number_format($claim->amount, 0, ',', '.') . ' untuk ' . $claim->project->name . ' telah disetujui.',
                '/keuangan/claim'
            );
        }

        return new ClaimResource($claim);
    }

    public function reject(RejectClaimRequest $request, DonationClaim $claim)
    {
        if ($claim->status !== ClaimStatus::Pending) {
            return response()->json(['message' => 'Klaim ini sudah diproses.'], 422);
        }

        $claim->update([
            'status'      => ClaimStatus::Rejected->value,
            'approved_by' => Auth::id(),
            'notes'       => $request->notes ?? $claim->notes,
        ]);

        $this->audit->log('rejected', $claim, new: ['status' => 'rejected']);

        if ($claim->claimed_by !== Auth::id()) {
            $this->notify->notifyUser(
                $claim->claimed_by,
                'claim.rejected',
                'Klaim donasi ditolak',
                'Klaim Rp' . number_format($claim->amount, 0, ',', '.') . ' ditolak.',
                '/keuangan/claim'
            );
        }

        return new ClaimResource($claim->fresh()->load('project', 'donationInput'));
    }

    private function approvedMessage(DonationClaim $claim): string
    {
        $input = $claim->donationInput;

        $default = "Assalamualaikum [Sapaan] [Nama],\n\n"
            . "Alhamdulillah, donasi Anda sebesar [Nominal] untuk *[Project]* telah kami verifikasi dan tersalurkan.\n\n"
            . "Semoga menjadi amal jariyah. Jazakumullah khairan.\n- BWKR";

        return app(\App\Services\SettingService::class)->renderTemplate('wa_approved_message', [
            '[Sapaan]'  => $input->salutation ?? '',
            '[Nama]'    => $input->donor_name,
            '[Nominal]' => 'Rp' . number_format($claim->amount, 0, ',', '.'),
            '[Project]' => $claim->project->name,
        ], $default);
    }

    public function export(Request $request, ReportExporter $exporter)
    {
        $format = $request->query('format', 'excel');
        $period = ReportPeriod::fromRequest($request);

        $claims = DonationClaim::with(['project', 'donationInput'])
            ->when($period['from'], fn($q) => $q->whereBetween('created_at', [$period['from'], $period['to']]))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->project_id, fn($q, $id) => $q->where('project_id', $id))
            ->latest()
            ->get();

        $rows = $claims->map(fn($c) => [
            optional($c->created_at)->format('d/m/Y H:i'),
            $c->donationInput?->ref_no ?? '-',
            $c->project?->name ?? '-',
            'Rp ' . number_format($c->amount, 0, ',', '.'),
            ucfirst($c->status->value),
            $c->notes ?: '-',
        ])->all();

        $approved = $claims->filter(fn($c) => $c->status->value === 'approved')->sum('amount');

        $payload = [
            'title'    => 'Laporan Klaim Donasi',
            'subtitle' => 'Panel Admin BWKR • ' . $period['label']
                . ($request->status ? ' • Status: ' . ucfirst($request->status) : ''),
            'columns'  => ['Tanggal', 'Ref Donasi', 'Project', 'Nominal', 'Status', 'Catatan'],
            'rows'     => $rows,
            'summary'  => [
                'Jumlah Klaim'  => $claims->count(),
                'Total Nominal' => 'Rp ' . number_format($claims->sum('amount'), 0, ',', '.'),
                'Disetujui'     => 'Rp ' . number_format($approved, 0, ',', '.'),
            ],
        ];

        return $exporter->respond($format, 'laporan-klaim-' . now()->format('Ymd-His'), $payload);
    }
}
