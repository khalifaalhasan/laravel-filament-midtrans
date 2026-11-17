<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(OrderService $orders): void
    {
        $accounts = [];
        foreach (['ceo' => 'CEO', 'operation' => 'Operation', 'user' => 'User'] as $role => $name) {
            $accounts[$role] = User::factory()->withRole($role)->create([
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
        $festivalPass = $music->ticketTypes()->create(['name' => 'Festival Pass', 'price' => 250_000, 'quota' => 500]);
        [$regular, $vip] = $tech->ticketTypes()->orderBy('id')->get();

        // Sample sales so the dashboard, order list, check-in and refund have data before any real Midtrans payment.
        $pay = function (User $buyer, $type, int $qty) use ($orders) {
            $order = $orders->reserve($buyer, $type, $qty);
            $orders->transition($order, OrderStatus::Paid, attributes: ['paid_at' => now(), 'payment_type' => 'qris']);

            return $order;
        };

        $pay($accounts['user'], $regular, 2); // user@example.com: 2 e-tickets ready for the check-in demo
        foreach (User::factory()->withRole('user')->count(5)->create() as $i => $buyer) {
            $pay($buyer, $i % 2 ? $vip : $regular, 1);
            $pay($buyer, $festivalPass, 2);
        }

        $checkedIn = $pay(User::factory()->withRole('user')->create(), $festivalPass, 1)->tickets()->first();
        $checkedIn->update(['checked_in_at' => now(), 'checked_in_by' => $accounts['operation']->id]);

        $orders->transition($orders->reserve(User::factory()->withRole('user')->create(), $regular, 1), OrderStatus::Expired);
    }
}
