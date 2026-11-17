<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /** Role and PO assignment snapshot, compared before/after save for the audit log. */
    public static function accessSnapshot(User $user): array
    {
        return [
            'roles' => $user->roles()->pluck('name')->sort()->values()->all(),
            'project_officer_of' => $user->managedEvents()->pluck('name')->sort()->values()->all(),
        ];
    }

    public static function logAccessChange(User $user, array $before): void
    {
        $after = self::accessSnapshot($user);

        if ($before !== $after) {
            activity('audit')
                ->performedOn($user)
                ->event('access_changed')
                ->withProperties(['old' => $before, 'new' => $after])
                ->log('Ubah role / penugasan PO');
        }
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
