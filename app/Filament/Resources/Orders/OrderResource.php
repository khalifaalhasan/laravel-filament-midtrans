<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Event;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// Orders are created only by checkout and changed only by the gateway or the refund action.
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $modelLabel = 'pesanan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('event', fn (Builder $q) => $q->manageableBy(auth()->user()))
            ->with(['event', 'items.ticketType', 'user']);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Event::class);
    }
}
