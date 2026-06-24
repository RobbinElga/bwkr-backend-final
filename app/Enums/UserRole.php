<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin      = 'admin';
    case Cs         = 'cs';
    case Fundraiser = 'fundraiser';
    case Donatur    = 'donatur';

    /** Role staff internal. Donatur bukan staff. */
    public function isStaff(): bool
    {
        return $this !== self::Donatur;
    }

    /** Wajib 2FA untuk semua role kecuali donatur. */
    public function requiresTwoFactor(): bool
    {
        return $this->isStaff();
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin      => 'Admin',
            self::Cs         => 'Customer Service',
            self::Fundraiser => 'Fundraiser',
            self::Donatur    => 'Donatur',
        };
    }
}
