<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventCategory: string implements HasLabel
{
    case Musik = 'musik';
    case Pameran = 'pameran';
    case Teater = 'teater';
    case Olahraga = 'olahraga';
    case Wisata = 'wisata';
    case Talkshow = 'talkshow';
    case Workshop = 'workshop';
    case Kompetisi = 'kompetisi';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
