<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

// Roles are reference data the app needs (e.g. registration assigns `user`), so they live in a migration, not a seeder.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['ceo', 'operation', 'user'] as $name) {
            Role::findOrCreate($name);
        }
    }

    public function down(): void
    {
        Role::whereIn('name', ['ceo', 'operation', 'user'])->delete();
    }
};
