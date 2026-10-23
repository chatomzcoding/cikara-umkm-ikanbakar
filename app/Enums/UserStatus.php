<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Suspended => 'Ditangguhkan',
            self::Inactive => 'Nonaktif',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success/10 text-success',
            self::Suspended => 'bg-warning/10 text-warning',
            self::Inactive => 'bg-slate-200 text-slate-700 dark:bg-navy-500 dark:text-navy-100',
        };
    }
}
