<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Models\TicketType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class TicketTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketTypes';

    protected static ?string $title = 'Jenis Tiket';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('price')
                    ->label('Harga')
                    ->prefix('Rp')
                    ->integer()
                    ->minValue(0)
                    ->required(),
                TextInput::make('bundle_size')
                    ->label('Isi per pembelian')
                    ->helperText('1 = tiket biasa. Isi lebih dari 1 untuk bundling (mis. 4 = paket 4 tiket).')
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                TextInput::make('quota')
                    ->label('Kuota (jumlah tiket)')
                    ->integer()
                    ->minValue(fn (?TicketType $record) => max(1, $record?->sold ?? 0))
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->columnSpanFull(),
                DateTimePicker::make('sale_starts_at')
                    ->label('Mulai dijual'),
                DateTimePicker::make('sale_ends_at')
                    ->label('Selesai dijual')
                    ->after('sale_starts_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn (int $state) => Number::currency($state, 'IDR', 'id', 0)),
                TextColumn::make('bundle_size')
                    ->label('Isi')
                    ->formatStateUsing(fn (int $state) => $state > 1 ? "Bundle {$state}" : 'Satuan'),
                TextColumn::make('quota')
                    ->label('Kuota'),
                TextColumn::make('sold')
                    ->label('Terjual'),
                TextColumn::make('sale_starts_at')
                    ->label('Mulai dijual')
                    ->dateTime(),
                TextColumn::make('sale_ends_at')
                    ->label('Selesai dijual')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
