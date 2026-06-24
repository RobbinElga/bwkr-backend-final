<?php

namespace App\Enums;

enum ReportCategory: string
{
    case Annual    = 'tahunan';
    case Financial = 'keuangan';
    case Program   = 'program';

    public function label(): string
    {
        return match ($this) {
            self::Annual    => 'Laporan Tahunan',
            self::Financial => 'Laporan Keuangan',
            self::Program   => 'Laporan Program',
        };
    }
}
