<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    private array $accessBefore = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->accessBefore = UserResource::accessSnapshot($this->record);
    }

    protected function afterSave(): void
    {
        UserResource::logAccessChange($this->record, $this->accessBefore);
    }
}
