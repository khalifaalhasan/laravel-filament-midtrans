<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

// Totals across the events this user manages: everything for CEO/Operation, assigned events for a PO.
class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->can('viewAny', Event::class);
    }

    protected function getStats(): array
    {
        $events = Event::manageableBy(auth()->user())->withSalesTotals()->get();
        $sold = (int) $events->sum('tickets_sold');
        $checkedIn = (int) $events->sum('checked_in');

        return [
            Stat::make('Pendapatan', Number::currency((int) $events->sum('revenue'), 'IDR', 'id', 0))
                ->description('Dari pesanan lunas'),
            Stat::make('Tiket terjual', Number::format($sold, locale: 'id')),
            Stat::make('Check-in', Number::format($checkedIn, locale: 'id'))
                ->description($sold > 0 ? round($checkedIn / $sold * 100).'% dari tiket terjual' : null),
        ];
    }
}
