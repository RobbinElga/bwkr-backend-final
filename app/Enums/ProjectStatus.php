<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Running   = 'berjalan';
    case Completed = 'selesai';
    case Draft     = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Running   => 'Berjalan',
            self::Completed => 'Selesai',
            self::Draft     => 'Draft',
        };
    }
}
