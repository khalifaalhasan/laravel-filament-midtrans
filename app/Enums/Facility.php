<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Fixed facility list from the event detail reference (03-detail-event.png). */
enum Facility: string implements HasLabel
{
    case FoodCourt = 'food-court';
    case Medis = 'medis';
    case Musholla = 'musholla';
    case Toilet = 'toilet';
    case PusatInformasi = 'pusat-informasi';
    case Merchandise = 'merchandise';
    case AreaParkir = 'area-parkir';

    public function getLabel(): string
    {
        return match ($this) {
            self::FoodCourt => 'Food Court',
            self::PusatInformasi => 'Pusat Informasi',
            self::AreaParkir => 'Area Parkir',
            default => ucfirst($this->value),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FoodCourt => 'heroicon-s-cake',
            self::Medis => 'heroicon-s-plus-circle',
            self::Musholla => 'heroicon-s-building-library',
            self::Toilet => 'heroicon-s-user-group',
            self::PusatInformasi => 'heroicon-s-information-circle',
            self::Merchandise => 'heroicon-s-shopping-bag',
            self::AreaParkir => 'heroicon-s-truck',
        };
    }
}
