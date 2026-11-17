<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Services\CheckInService;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

class CheckIn extends Page
{
    protected string $view = 'filament.pages.check-in';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $title = 'Check-in';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->can('viewAny', Event::class);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode tiket')
                    ->helperText('Ketik kode atau scan QR dengan scanner (scanner mengetik kode lalu Enter).')
                    ->required()
                    ->autofocus()
                    ->autocomplete(false),
            ])
            ->statePath('data');
    }

    public function checkIn(CheckInService $service): void
    {
        $code = $this->form->getState()['code'];

        try {
            $ticket = $service->checkIn($code, auth()->user());
            Notification::make()->success()->title('Check-in berhasil')
                ->body("{$ticket->code} · {$ticket->event->name} · {$ticket->order->user->name}")->send();
        } catch (RuntimeException $e) {
            Notification::make()->danger()->title('Check-in ditolak')->body($e->getMessage())->send();
        }

        $this->form->fill();
    }
}
