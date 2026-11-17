<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Region: string implements HasLabel
{
    case Sumatera = 'sumatera';
    case Jabodetabek = 'jabodetabek';
    case JawaBarat = 'jawa-barat';
    case DiyJateng = 'diy-jateng';
    case JawaTimur = 'jawa-timur';
    case Kalimantan = 'kalimantan';
    case Sulawesi = 'sulawesi';
    case BaliNusra = 'bali-nusra';

    public function getLabel(): string
    {
        return match ($this) {
            self::Jabodetabek => 'Jabodetabek',
            self::JawaBarat => 'Jawa Barat',
            self::DiyJateng => 'DIY-Jateng',
            self::JawaTimur => 'Jawa Timur',
            self::BaliNusra => 'Bali & Nusa Tenggara',
            default => ucfirst($this->value),
        };
    }
}
