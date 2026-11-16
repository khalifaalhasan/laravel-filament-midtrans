<?php

namespace Database\Seeders;

use App\Models\Event;
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

        $po = User::factory()->withRole('user')->create([
            'name' => 'Demo Project Officer',
            'email' => 'po@example.com',
        ]);
        Event::factory()->published()->create(['name' => 'Tech Conference', 'slug' => 'tech-conference'])
            ->officers()->attach($po);
        Event::factory()->published()->create(['name' => 'Music Festival', 'slug' => 'music-festival']);
    }
}
