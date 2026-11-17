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

    /** Heroicon (solid) closest to the reference category icons. */
    public function icon(): string
    {
        return match ($this) {
            self::Musik => 'heroicon-s-musical-note',
            self::Pameran => 'heroicon-s-building-library',
            self::Teater => 'heroicon-s-film',
            self::Olahraga => 'heroicon-s-flag',
            self::Wisata => 'heroicon-s-map-pin',
            self::Talkshow => 'heroicon-s-microphone',
            self::Workshop => 'heroicon-s-presentation-chart-bar',
            self::Kompetisi => 'heroicon-s-trophy',
        };
    }
}
