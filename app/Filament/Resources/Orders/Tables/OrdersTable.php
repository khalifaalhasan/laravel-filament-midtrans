<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentGatewayException;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->copyable(),
                TextColumn::make('event.name')->label('Event')->searchable(),
                TextColumn::make('user.email')->label('Pembeli')->searchable(),
                TextColumn::make('ticketType.name')->label('Tiket'),
                TextColumn::make('quantity')->label('Qty'),
                TextColumn::make('total')->formatStateUsing(fn (int $state) => Number::currency($state, 'IDR', 'id', 0)),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_type')->label('Metode')->toggleable(),
                TextColumn::make('paid_at')->label('Dibayar')->dateTime()->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class),
                SelectFilter::make('event')->relationship('event', 'name'),
            ])
            ->recordActions([
                Action::make('refund')
                    ->label('Refund')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Order $record) => 'Kembalikan '.Number::currency($record->total, 'IDR', 'id', 0).' ke pembeli lewat Midtrans. Tindakan ini tidak bisa dibatalkan.')
                    ->schema([
                        Textarea::make('reason')->label('Alasan')->required()->maxLength(255),
                    ])
                    ->authorize('refund')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::Paid)
                    ->action(function (Order $record, array $data, OrderService $orders) {
                        try {
                            $refunded = $orders->refund($record, $data['reason']);
                        } catch (PaymentGatewayException $e) {
                            Notification::make()->danger()->title('Refund gagal')->body($e->getMessage())->send();

                            return;
                        }

                        $refunded
                            ? Notification::make()->success()->title('Refund berhasil')->send()
                            : Notification::make()->warning()->title('Refund diproses')->body('Status akan diperbarui saat Midtrans mengonfirmasi.')->send();
                    }),
            ]);
    }
}
