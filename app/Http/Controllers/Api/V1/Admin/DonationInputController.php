<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DonationSource;
use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\StoreManualDonationRequest;
use App\Http\Resources\DonationInputResource;
use App\Models\DonationInput;
use App\Services\AuditService;
use App\Services\ProofFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use App\Services\ReportExporter;
use App\Support\ReportPeriod;

class DonationInputController extends Controller
{
    public function __construct(
        private readonly ProofFileService $proofs,
        private readonly AuditService $audit,
        private readonly NotificationService $notify,
    ) {}

    public function index(Request $request)
    {
        $donations = DonationInput::with('bankAccount')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->source, fn($q, $s) => $q->where('source', $s))
            ->when($request->phone, fn($q, $p) => $q->where('donor_phone_hash', DonationInput::hashPhone($p)))
            ->when($request->search, fn($q, $s) => $q->where('donor_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return DonationInputResource::collection($donations);
    }

    public function store(StoreManualDonationRequest $request)
    {
        $data = collect($request->validated())->except('proof')->all();
        $data['ref_no']   = DonationInput::generateRefNo();
        $data['source']   = DonationSource::Manual->value;
        $data['status']   = DonationStatus::Pending->value;
        $data['input_by'] = Auth::id();

        if ($request->hasFile('proof')) {
            $data['proof_file'] = $this->proofs->store($request->file('proof'));
        }

        $donation = DonationInput::create($data);
        $this->audit->log('created', $donation, new: $this->auditable($donation));
        $this->notify->notifyRoles(
            ['super_admin', 'admin'],
            'donation.created',
            'Donasi baru diinput',
            "{$donation->donor_name} — Rp" . number_format($donation->amount, 0, ',', '.'),
            '/keuangan/input'
        );

        return (new DonationInputResource($donation->load('bankAccount')))->response()->setStatusCode(201);
    }

    public function show(DonationInput $donation)
    {
        return new DonationInputResource($donation->load('bankAccount'));
    }

    /** Stream bukti transfer (privat) — hanya staff terautentikasi. */
    public function proof(DonationInput $donation)
    {
        $path = $donation->proof_file;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Data audit — sembunyikan nomor HP & path bukti. */
    private function auditable(DonationInput $d): array
    {
        return collect($d->toArray())->except(['donor_phone', 'proof_file'])->all();
    }

    public function export(Request $request, ReportExporter $exporter)
    {
        $format = $request->query('format', 'excel');
        $period = ReportPeriod::fromRequest($request);

        $donations = DonationInput::with('bankAccount')
            ->when($period['from'], fn($q) => $q->whereBetween('created_at', [$period['from'], $period['to']]))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->source, fn($q, $s) => $q->where('source', $s))
            ->when($request->phone, fn($q, $p) => $q->where('donor_phone_hash', DonationInput::hashPhone($p)))
            ->when($request->search, fn($q, $s) => $q->where('donor_name', 'like', "%{$s}%"))
            ->latest()
            ->get();

        $rows = $donations->map(fn($d) => [
            $d->ref_no,
            optional($d->created_at)->format('d/m/Y H:i'),
            $d->donor_alias ?: $d->donor_name,
            'Rp ' . number_format($d->amount, 0, ',', '.'),
            ucfirst($d->source->value),
            $d->bankAccount?->bank_name ?? '-',
            ucfirst($d->status->value),
        ])->all();

        $payload = [
            'title'    => 'Laporan Donasi Masuk',
            'subtitle' => 'Panel Admin BWKR • ' . $period['label']
                . ($request->status ? ' • Status: ' . ucfirst($request->status) : '')
                . ($request->source ? ' • Sumber: ' . ucfirst($request->source) : '')
                . ($request->search ? ' • Cari: "' . $request->search . '"' : ''),
            'columns'  => ['Ref No', 'Tanggal', 'Donatur', 'Nominal', 'Sumber', 'Rekening', 'Status'],
            'rows'     => $rows,
            'summary'  => [
                'Jumlah Transaksi' => $donations->count(),
                'Total Nominal'    => 'Rp ' . number_format($donations->sum('amount'), 0, ',', '.'),
            ],
        ];

        return $exporter->respond($format, 'laporan-donasi-' . now()->format('Ymd-His'), $payload);
    }
}
