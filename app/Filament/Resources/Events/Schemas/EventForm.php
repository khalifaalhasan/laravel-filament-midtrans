<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\Facility;
use App\Enums\Region;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, ?string $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                RichEditor::make('description')
                    ->label('Deskripsi')
                    ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                    ->columnSpanFull(),
                RichEditor::make('terms')
                    ->label('Syarat & Ketentuan')
                    ->toolbarButtons([['bold', 'italic', 'underline'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                    ->columnSpanFull(),
                CheckboxList::make('facilities')
                    ->label('Fasilitas')
                    ->options(Facility::class)
                    ->columns(4)
                    ->columnSpanFull(),
                TextInput::make('instagram_url')
                    ->label('Instagram')
                    ->url()
                    ->prefixIcon('heroicon-o-link'),
                TextInput::make('tiktok_url')
                    ->label('TikTok')
                    ->url()
                    ->prefixIcon('heroicon-o-link'),
                TextInput::make('location')
                    ->required(),
                Select::make('status')
                    ->options(EventStatus::class)
                    ->default(EventStatus::Draft)
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->required()
                    ->after('starts_at'),
                Select::make('category')
                    ->label('Kategori')
                    ->options(EventCategory::class)
                    ->required(),
                Select::make('region')
                    ->label('Wilayah')
                    ->options(Region::class)
                    ->required(),
                TextInput::make('organizer_name')
                    ->label('Penyelenggara'),
                FileUpload::make('organizer_logo_path')
                    ->label('Logo penyelenggara')
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('organizers'),
                FileUpload::make('poster_path')
                    ->label('Poster (kartu event, rasio 5:2)')
                    ->image()
                    ->disk('public')
                    ->directory('posters'),
                FileUpload::make('banner_path')
                    ->label('Banner (carousel beranda, rasio 10:3)')
                    ->image()
                    ->disk('public')
                    ->directory('banners'),
                Toggle::make('is_featured')
                    ->label('Tampilkan di banner beranda')
                    ->helperText('Event featured dengan banner muncul di carousel paling atas beranda.'),
            ]);
    }
}
