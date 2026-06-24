<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationClaim;
use App\Models\Expense;
use Illuminate\Http\Request;

class CashLedgerController extends Controller
{
    /** GET /admin/keuangan/ledger — buku kas masuk/keluar + saldo berjalan. */
    public function index(Request $request)
    {
        // MASUK: klaim donasi yang sudah approved
        $masuk = DonationClaim::with(['project', 'donationInput'])
            ->where('status', 'approved')
            ->get()
            ->map(fn($c) => [
                'date'        => $c->approved_at ?? $c->created_at,
                'type'        => 'masuk',
                'description' => 'Donasi ' . ($c->donationInput?->donor_name ?? '-')
                    . ' → ' . ($c->project?->name ?? 'Wakaf Umum'),
                'ref'         => $c->donationInput?->ref_no,
                'amount'      => (int) $c->amount,
            ]);

        // KELUAR: pengeluaran yang sudah approved
        $keluar = Expense::with('project')
            ->where('status', 'approved')
            ->get()
            ->map(fn($e) => [
                'date'        => $e->approved_at ?? $e->created_at,
                'type'        => 'keluar',
                'description' => 'Pengeluaran ' . ($e->project?->name ?? '-'),
                'ref'         => null,
                'amount'      => (int) $e->amount,
            ]);

        // Gabung + urut menaik untuk hitung saldo berjalan
        $entries = $masuk->concat($keluar)->sortBy('date')->values();

        $balance = 0;
        $rows = $entries->map(function ($e) use (&$balance) {
            $balance += $e['type'] === 'masuk' ? $e['amount'] : -$e['amount'];
            return [
                'date'        => $e['date'],
                'type'        => $e['type'],
                'description' => $e['description'],
                'ref'         => $e['ref'],
                'amount'      => $e['amount'],
                'balance'     => $balance,
            ];
        });

        $totalMasuk  = (int) $masuk->sum('amount');
        $totalKeluar = (int) $keluar->sum('amount');

        return response()->json([
            'data'    => $rows->reverse()->values(), // terbaru dulu
            'summary' => [
                'total_masuk'  => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'saldo'        => $totalMasuk - $totalKeluar,
            ],
        ]);
    }
}
