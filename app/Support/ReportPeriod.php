<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportPeriod
{
    /** @return array{from: ?Carbon, to: ?Carbon, label: string} */
    public static function fromRequest(Request $r): array
    {
        $to = now();

        return match ($r->query('period')) {
            '1d'     => ['from' => now()->subDay(),     'to' => $to, 'label' => '1 hari terakhir'],
            '7d'     => ['from' => now()->subDays(7),   'to' => $to, 'label' => '7 hari terakhir'],
            '30d'    => ['from' => now()->subDays(30),  'to' => $to, 'label' => '30 hari terakhir'],
            '90d'    => ['from' => now()->subDays(90),  'to' => $to, 'label' => '90 hari terakhir'],
            'custom' => self::custom($r),
            default  => ['from' => null, 'to' => null, 'label' => 'Semua periode'],
        };
    }

    private static function custom(Request $r): array
    {
        $from = $r->query('date_from') ? Carbon::parse($r->query('date_from'))->startOfDay() : null;
        $to   = $r->query('date_to') ? Carbon::parse($r->query('date_to'))->endOfDay() : now();

        $label = 'Periode ' . ($from ? $from->translatedFormat('d M Y') : '…') . ' – ' . $to->translatedFormat('d M Y');

        return ['from' => $from, 'to' => $to, 'label' => $label];
    }
}
