<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DonationInput;   // ← diganti dari Donation
use App\Models\Expense;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class KeuanganDashboardController extends Controller
{
    public function index()
    {
        // ===== 1. RINGKASAN KAS =====
        $totalMasuk  = DonationInput::where('status', 'claimed')->sum('amount');
        $totalKeluar = Expense::sum('amount');
        $saldo       = $totalMasuk - $totalKeluar;

        // ===== 2. TREN BULANAN (12 bulan terakhir) =====
        $trenMasuk = DonationInput::where('status', 'claimed')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bulan"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $trenKeluar = Expense::where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bulan"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $tren = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $tren[] = [
                'bulan'  => $key,
                'masuk'  => (int) ($trenMasuk[$key] ?? 0),
                'keluar' => (int) ($trenKeluar[$key] ?? 0),
            ];
        }

        // ===== 3. BREAKDOWN PER PROGRAM =====
        $perProgram = Program::withSum(
            ['donations' => fn($q) => $q->where('status', 'claimed')],
            'amount'
        )
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'nama'  => $p->name,
                'total' => (int) ($p->donations_sum_amount ?? 0),
            ])
            ->sortByDesc('total')
            ->values();

        // ===== 4. PENGELUARAN PER KATEGORI =====
        $perKategori = Expense::query()
            ->with('project:id,name')
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->groupBy('project_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($e) => [
                'kategori' => $e->project?->name ?? 'Tanpa Proyek',
                'total'    => (int) $e->total,
            ]);

        // ===== RESPONS =====
        return response()->json([
            'ringkasan' => [
                'total_masuk'  => (int) $totalMasuk,
                'total_keluar' => (int) $totalKeluar,
                'saldo'        => (int) $saldo,
            ],
            'tren'         => $tren,
            'per_program'  => $perProgram,
            'per_kategori' => $perKategori,
        ]);
    }
}
