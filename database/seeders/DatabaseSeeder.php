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
        $tech = Event::factory()->published()->create(['name' => 'Tech Conference', 'slug' => 'tech-conference']);
        $tech->officers()->attach($po);
        $music = Event::factory()->published()->create(['name' => 'Music Festival', 'slug' => 'music-festival']);

        $tech->ticketTypes()->createMany([
            ['name' => 'Regular', 'price' => 150_000, 'quota' => 100],
            ['name' => 'VIP', 'price' => 500_000, 'quota' => 20],
        ]);
        $music->ticketTypes()->create(['name' => 'Festival Pass', 'price' => 250_000, 'quota' => 500]);
    }
}
