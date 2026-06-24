<?php

namespace App\Enums;

enum ProgramStatus: string
{
    case Active   = 'aktif';
    case Inactive = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Aktif',
            self::Inactive => 'Non-aktif',
        };
    }
}
