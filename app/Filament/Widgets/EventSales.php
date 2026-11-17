<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class EventSales extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Penjualan per event';

    public static function canView(): bool
    {
        return auth()->user()->can('viewAny', Event::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Event::manageableBy(auth()->user())->withSalesTotals())
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Event')->searchable(),
                TextColumn::make('starts_at')->label('Mulai')->date()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('revenue')->label('Pendapatan')->sortable()
                    ->formatStateUsing(fn ($state) => Number::currency((int) $state, 'IDR', 'id', 0))
                    ->default(0),
                TextColumn::make('tickets_sold')->label('Tiket terjual')->sortable(),
                TextColumn::make('checked_in')->label('Check-in')->sortable(),
            ]);
    }
}
