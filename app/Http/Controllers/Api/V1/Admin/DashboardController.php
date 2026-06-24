<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\DonationClaim;
use App\Models\DonationInput;
use App\Models\Expense;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ReportExporter;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalRaised    = (int) DonationClaim::where('status', 'approved')->sum('amount');
        $totalDisbursed = (int) Expense::where('status', 'approved')->sum('amount');

        // Saldo per rekening = (klaim approved dari donasi via rekening ini) - (pengeluaran approved dari rekening ini)
        $accounts = BankAccount::where('is_active', true)->get()->map(function (BankAccount $acc) {
            $incoming = (int) DonationClaim::where('status', 'approved')
                ->whereHas('donationInput', fn($q) => $q->where('bank_account_id', $acc->id))
                ->sum('amount');

            $outgoing = (int) Expense::where('status', 'approved')
                ->where('bank_account_id', $acc->id)
                ->sum('amount');

            return [
                'id'        => $acc->id,
                'bank_name' => $acc->bank_name,
                'balance'   => $incoming - $outgoing,
            ];
        });

        return response()->json([
            'programs' => [
                'active' => Program::where('status', 'aktif')->count(),
            ],
            'projects' => [
                'total'     => Project::count(),
                'running'   => Project::where('status', 'berjalan')->count(),
                'completed' => Project::where('status', 'selesai')->count(),
            ],
            'funds' => [
                'total_raised'    => $totalRaised,
                'total_disbursed' => $totalDisbursed,
                'remaining'       => $totalRaised - $totalDisbursed,
            ],
            'donors' => [
                'total' => DonationInput::distinct('donor_phone_hash')->count('donor_phone_hash'),
            ],
            'staff_active' => User::where('role', '!=', 'donatur')->where('is_active', true)->count(),
            'balance_per_account' => $accounts,
        ]);
    }
    /** GET /admin/dashboard/trends — tren donasi (klaim approved) per bulan. */
    public function trends(Request $request): JsonResponse
    {
        // Batasi rentang 1–24 bulan supaya tidak bisa diakali untuk query berat
        $months = max(1, min($request->integer('months', 12), 24));

        $start = now()->startOfMonth()->subMonths($months - 1);

        // Ambil total per bulan dalam satu query (efisien)
        $totals = DonationClaim::where('status', 'approved')
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(approved_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym'); // contoh: ['2026-05' => 1500000, ...]

        // Bangun deret bulan berurutan; bulan tanpa data diisi 0
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');
            $series[] = [
                'month' => $key,                       // "2026-05"
                'total' => (int) ($totals[$key] ?? 0), // rupiah
            ];
        }

        return response()->json(['data' => $series]);
    }
    /** GET /admin/dashboard/recent-donations — transaksi donasi terbaru (ringkas). */
    public function recentDonations(Request $request): JsonResponse
    {
        // Batasi 1–20 baris
        $limit = max(1, min($request->integer('limit', 5), 20));

        $rows = DonationInput::with([
            'program:id,name',
            'project:id,name',
        ])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn(DonationInput $d) => [
                'ref_no'     => $d->ref_no,
                'donor_name' => $d->donor_name,
                // tujuan: project diutamakan, lalu program, lalu null
                'target'     => $d->project?->name ?? $d->program?->name,
                'amount'     => (int) $d->amount,
                'status'     => $d->status->value,   // pending | claimed | rejected
                'source'     => $d->source->value,   // online | manual | gateway
                'created_at' => $d->created_at,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function export(Request $request, ReportExporter $exporter)
    {
        $format = $request->query('format', 'excel');
        $period = \App\Support\ReportPeriod::fromRequest($request);
        $from = $period['from'];
        $to   = $period['to'];

        // periode sebelumnya yang setara (untuk hitung naik/turun)
        $prevFrom = $prevTo = null;
        if ($from) {
            $len      = $from->diffInSeconds($to);
            $prevTo   = $from->copy();
            $prevFrom = $from->copy()->subSeconds($len);
        }

        // Dana terkumpul = klaim approved (approved_at) dalam periode
        $raisedCurr = (int) DonationClaim::where('status', 'approved')
            ->when($from, fn($q) => $q->whereBetween('approved_at', [$from, $to]))->sum('amount');
        $raisedPrev = $prevFrom
            ? (int) DonationClaim::where('status', 'approved')->whereBetween('approved_at', [$prevFrom, $prevTo])->sum('amount')
            : 0;

        // Donatur unik = berdasarkan donasi masuk (created_at) dalam periode
        $donorCurr = (int) DonationInput::when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->distinct('donor_phone_hash')->count('donor_phone_hash');
        $donorPrev = $prevFrom
            ? (int) DonationInput::whereBetween('created_at', [$prevFrom, $prevTo])->distinct('donor_phone_hash')->count('donor_phone_hash')
            : 0;

        $pct = function (int $curr, int $prev): string {
            if ($prev === 0) return $curr > 0 ? '▲ +100%' : '—';
            $delta = round(($curr - $prev) / $prev * 100, 1);
            $arrow = $delta > 0 ? '▲ +' : ($delta < 0 ? '▼ ' : '');
            return $arrow . $delta . '%';
        };

        $totalDisbursed = (int) Expense::where('status', 'approved')
            ->when($from, fn($q) => $q->whereBetween('approved_at', [$from, $to]))->sum('amount');

        $rows = BankAccount::where('is_active', true)->get()->map(function (BankAccount $acc) {
            $incoming = (int) DonationClaim::where('status', 'approved')
                ->whereHas('donationInput', fn($q) => $q->where('bank_account_id', $acc->id))->sum('amount');
            $outgoing = (int) Expense::where('status', 'approved')->where('bank_account_id', $acc->id)->sum('amount');
            return [$acc->bank_name, 'Rp ' . number_format($incoming - $outgoing, 0, ',', '.')];
        })->all();

        $summary = [
            'Periode'               => $period['label'],
            'Dana Terkumpul'        => 'Rp ' . number_format($raisedCurr, 0, ',', '.'),
            'Perubahan Dana'        => $from ? $pct($raisedCurr, $raisedPrev) : '—',
            'Total Donatur'         => number_format($donorCurr, 0, ',', '.'),
            'Perubahan Donatur'     => $from ? $pct($donorCurr, $donorPrev) : '—',
            'Total Dana Tersalur'   => 'Rp ' . number_format($totalDisbursed, 0, ',', '.'),
            'Program Aktif'         => Program::where('status', 'aktif')->count(),
            'Total Project'         => Project::count(),
            'Staf Aktif'            => User::where('role', '!=', 'donatur')->where('is_active', true)->count(),
        ];

        return $exporter->respond($format, 'ringkasan-dashboard-' . now()->format('Ymd-His'), [
            'title'    => 'Ringkasan Dashboard',
            'subtitle' => 'Panel Admin BWKR • ' . $period['label'],
            'columns'  => ['Rekening', 'Saldo (kumulatif)'],
            'rows'     => $rows,
            'summary'  => $summary,
        ]);
    }
}
