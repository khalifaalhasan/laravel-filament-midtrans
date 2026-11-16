<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        foreach (['ceo' => 'CEO', 'operation' => 'Operation', 'user' => 'User'] as $role => $name) {
            User::factory()->withRole($role)->create([
                'name' => "Demo {$name}",
                'email' => "{$role}@example.com",
            ]);
        }
    }
}
