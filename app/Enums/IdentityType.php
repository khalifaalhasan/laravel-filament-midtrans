<?php

namespace App\Enums;

enum IdentityType: string
{
    case Ktp = 'ktp';
    case Sim = 'sim';
    case Paspor = 'paspor';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP',
            self::Sim => 'SIM',
            self::Paspor => 'Paspor',
        };
    }

    /** Validation rule for the number of this identity type. */
    public function numberRule(): string
    {
        return match ($this) {
            self::Ktp => 'digits:16',
            self::Sim => 'digits_between:12,14',
            self::Paspor => 'regex:/^[A-Za-z0-9]{6,9}$/',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
