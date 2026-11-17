<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

// Read-only view of sensitive actions (refund, price change, role/PO change). CEO only.
class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'audit log';

    protected static ?string $pluralModelLabel = 'Audit Log';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('ceo');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('log_name', 'audit')->with(['causer', 'subject']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
                TextColumn::make('causer.name')->label('Pelaku')->placeholder('Sistem'),
                TextColumn::make('description')->label('Aksi'),
                TextColumn::make('subject_type')->label('Objek')
                    ->formatStateUsing(fn (?string $state, Activity $record) => class_basename((string) $state).' #'.$record->subject_id),
                TextColumn::make('details')->label('Detail')->wrap()
                    ->state(fn (Activity $record) => json_encode(
                        array_filter([...($record->attribute_changes?->toArray() ?? []), ...($record->properties?->toArray() ?? [])]),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    )),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')->label('Jenis')->options([
                    'refund' => 'Refund',
                    'updated' => 'Ubah harga',
                    'access_changed' => 'Ubah role / PO',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
