<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DonationStatus;
use App\Enums\DonorTier;
use App\Http\Controllers\Controller;
use App\Http\Resources\DonationInputResource;
use App\Models\DonationInput;
use App\Models\DonorProfile;
use App\Services\AuditService;
use App\Services\CrmService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ReportExporter;
use App\Support\ReportPeriod;

class CrmDonorController extends Controller
{
    public function __construct(
        private readonly CrmService $crm,
        private readonly AuditService $audit,
    ) {}

    /** GET /admin/crm/donors — daftar donatur teragregasi. */
    public function index(Request $request)
    {
        $premium = $this->crm->premiumHashes();

        $page = DonationInput::query()
            ->selectRaw("donor_phone_hash, COUNT(*) as donation_count, SUM(CASE WHEN status != 'rejected' THEN amount ELSE 0 END) as total_donated, MAX(created_at) as last_donated_at, MAX(id) as latest_id")
            ->whereNotNull('donor_phone_hash')
            ->when($request->search, function ($q, $s) {
                $hash = DonationInput::hashPhone($s);
                $q->where(fn($w) => $w->where('donor_name', 'like', "%{$s}%")->orWhere('donor_phone_hash', $hash));
            })
            ->when($request->tier === 'premium', fn($q) => $q->whereIn('donor_phone_hash', $premium ?: ['__none__']))
            ->when($request->tier === 'reguler', fn($q) => $q->whereNotIn('donor_phone_hash', $premium))
            ->groupBy('donor_phone_hash')
            ->orderByDesc('last_donated_at')
            ->paginate($request->integer('per_page', 15));

        $latest   = DonationInput::whereIn('id', collect($page->items())->pluck('latest_id'))->get()->keyBy('id');
        $profiles = DonorProfile::whereIn('donor_phone_hash', collect($page->items())->pluck('donor_phone_hash'))->get()->keyBy('donor_phone_hash');

        $page->getCollection()->transform(function ($row) use ($latest, $profiles) {
            $rep = $latest->get($row->latest_id);

            return [
                'donor_phone_hash' => $row->donor_phone_hash,
                'name'             => $rep?->donor_name,
                'phone'            => $rep?->donor_phone,
                'total_donated'    => (int) $row->total_donated,
                'donation_count'   => (int) $row->donation_count,
                'last_donated_at'  => $row->last_donated_at,
                'tier'             => $profiles->get($row->donor_phone_hash)?->tier->value ?? 'reguler',
            ];
        });

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    /** GET /admin/crm/donors/{hash} — detail + riwayat donasi donatur. */
    public function show(string $hash)
    {
        $donations = DonationInput::with('bankAccount')
            ->where('donor_phone_hash', $hash)->latest()->get();

        abort_if($donations->isEmpty(), 404);

        $rep     = $donations->first();
        $profile = DonorProfile::firstWhere('donor_phone_hash', $hash);

        return response()->json([
            'donor' => [
                'donor_phone_hash' => $hash,
                'name'             => $rep->donor_name,
                'phone'            => $rep->donor_phone,
                'tier'             => $profile?->tier->value ?? 'reguler',
                'notes'            => $profile?->notes,
                'total_donated'    => (int) $donations->where('status', '!=', DonationStatus::Rejected)->sum('amount'),
                'donation_count'   => $donations->count(),
            ],
            'donations' => DonationInputResource::collection($donations),
        ]);
    }

    /** PUT /admin/crm/donors/{hash}/tier — ubah tier donatur. */
    public function updateTier(Request $request, string $hash)
    {
        $data = $request->validate(['tier' => ['required', Rule::enum(DonorTier::class)]]);

        $rep = DonationInput::where('donor_phone_hash', $hash)->latest()->first();
        abort_if(! $rep, 404, 'Donatur tidak ditemukan.');

        $profile = DonorProfile::updateOrCreate(
            ['donor_phone_hash' => $hash],
            ['donor_name' => $rep->donor_name, 'tier' => $data['tier']],
        );

        $this->audit->log('tier_changed', $profile, new: ['tier' => $data['tier']]);

        return response()->json(['message' => 'Tier diperbarui.', 'tier' => $profile->tier->value]);
    }

    public function export(Request $request, ReportExporter $exporter)
    {
        $format  = $request->query('format', 'excel');
        $period  = ReportPeriod::fromRequest($request);
        $premium = $this->crm->premiumHashes();

        $agg = DonationInput::query()
            ->selectRaw("donor_phone_hash, COUNT(*) as donation_count, SUM(CASE WHEN status != 'rejected' THEN amount ELSE 0 END) as total_donated, MAX(created_at) as last_donated_at, MAX(id) as latest_id")
            ->whereNotNull('donor_phone_hash')
            ->when($period['from'], fn($q) => $q->whereBetween('created_at', [$period['from'], $period['to']]))
            ->when($request->search, function ($q, $s) {
                $hash = DonationInput::hashPhone($s);
                $q->where(fn($w) => $w->where('donor_name', 'like', "%{$s}%")->orWhere('donor_phone_hash', $hash));
            })
            ->when($request->tier === 'premium', fn($q) => $q->whereIn('donor_phone_hash', $premium ?: ['__none__']))
            ->when($request->tier === 'reguler', fn($q) => $q->whereNotIn('donor_phone_hash', $premium))
            ->groupBy('donor_phone_hash')
            ->orderByDesc('last_donated_at')
            ->get();

        $latest   = DonationInput::whereIn('id', $agg->pluck('latest_id'))->get()->keyBy('id');
        $profiles = DonorProfile::whereIn('donor_phone_hash', $agg->pluck('donor_phone_hash'))->get()->keyBy('donor_phone_hash');

        $rows = $agg->map(function ($row) use ($latest, $profiles) {
            $rep  = $latest->get($row->latest_id);
            $tier = $profiles->get($row->donor_phone_hash)?->tier->value ?? 'reguler';

            return [
                $rep?->donor_name ?? '-',
                'Rp ' . number_format((int) $row->total_donated, 0, ',', '.'),
                (int) $row->donation_count,
                $row->last_donated_at ? \Illuminate\Support\Carbon::parse($row->last_donated_at)->format('d/m/Y') : '-',
                ucfirst($tier),
            ];
        })->all();

        $payload = [
            'title'    => 'Laporan Donatur',
            'subtitle' => 'Panel Admin BWKR • ' . $period['label']
                . ($request->tier ? ' • Tier: ' . ucfirst($request->tier) : ''),
            'columns'  => ['Nama', 'Total Donasi', 'Transaksi', 'Terakhir Donasi', 'Tier'],
            'rows'     => $rows,
            'summary'  => [
                'Jumlah Donatur' => count($rows),
                'Total Donasi'   => 'Rp ' . number_format((int) $agg->sum('total_donated'), 0, ',', '.'),
            ],
        ];

        return $exporter->respond($format, 'laporan-donatur-' . now()->format('Ymd-His'), $payload);
    }
}
