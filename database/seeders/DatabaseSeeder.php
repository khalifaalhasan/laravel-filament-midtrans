<?php

namespace Database\Seeders;

use App\Enums\EventCategory;
use App\Enums\OrderStatus;
use App\Enums\Region;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
        // Home page catalogue: [name, category, region, organizer, days from now, duration days, featured, prices]
        $catalogue = [
            ['Tech Conference', EventCategory::Talkshow, Region::Jabodetabek, 'Inovasi Nusantara', 20, 1, true, []],
            ['Music Festival', EventCategory::Musik, Region::DiyJateng, 'Nara Kreatif', 35, 2, true, []],
            ['Senandung Senja Fest', EventCategory::Musik, Region::JawaBarat, 'Senja Production', 12, 1, true, [75_000, 150_000]],
            ['Pameran Seni Rupa Nusantara', EventCategory::Pameran, Region::Jabodetabek, 'Galeri Kita', 5, 14, false, [40_000]],
            ['Teater Bayang Kota', EventCategory::Teater, Region::DiyJateng, 'Sanggar Bayang', 18, 1, false, [55_000, 90_000]],
            ['Lari Pagi 10K Surabaya', EventCategory::Olahraga, Region::JawaTimur, 'Komunitas Lari Timur', 26, 1, true, [150_000]],
            ['Jelajah Danau Toba', EventCategory::Wisata, Region::Sumatera, 'Toba Trip', 40, 3, false, [350_000]],
            ['Talkshow Kreator Digital', EventCategory::Talkshow, Region::Jabodetabek, 'Ruang Kreator', 8, 1, false, [25_000]],
            ['Workshop Manual Brew', EventCategory::Workshop, Region::JawaBarat, 'Kopi Pagi', 15, 1, false, [120_000]],
            ['Turnamen E-Sport Kampus', EventCategory::Kompetisi, Region::Sulawesi, 'Liga Mahasiswa', 30, 2, false, [30_000]],
            ['Festival Budaya Borneo', EventCategory::Pameran, Region::Kalimantan, 'Borneo Kreatif', 45, 3, false, [50_000]],
            ['Konser Akustik Pantai', EventCategory::Musik, Region::BaliNusra, 'Ombak Musik', 22, 1, true, [99_900, 199_000]],
            ['Weekend Hardcore', EventCategory::Musik, Region::DiyJateng, 'Kota Hardcore', 3, 1, false, [40_000]],
            ['Expo Otomotif Jatim', EventCategory::Pameran, Region::JawaTimur, 'Garasi Expo', 10, 3, false, [25_000]],
            ['Jazz di Kebun', EventCategory::Musik, Region::JawaBarat, 'Kebun Jazz', 28, 2, false, [94_000, 250_000]],
            ['Lomba Robotik Pelajar', EventCategory::Kompetisi, Region::Jabodetabek, 'Robo Edu', 50, 1, false, [35_000]],
        ];

        $events = [];
        foreach ($catalogue as $i => [$name, $category, $region, $organizer, $inDays, $days, $featured, $prices]) {
            $startsAt = now()->addDays($inDays)->setTime(14, 0);
            $event = Event::factory()->published()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'category' => $category,
                'region' => $region,
                'organizer_name' => $organizer,
                'organizer_logo_path' => DemoArtwork::logo($organizer, $i),
                'poster_path' => DemoArtwork::poster($name, $i),
                'banner_path' => $featured ? DemoArtwork::banner($name, $i) : null,
                'is_featured' => $featured,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addDays($days - 1)->setTime(23, 0),
            ]);
            foreach ($prices as $j => $price) {
                $event->ticketTypes()->create(['name' => $j === 0 ? 'Reguler' : 'VIP', 'price' => $price, 'quota' => 200]);
            }
            $events[$name] = $event;
        }

        $tech = $events['Tech Conference'];
        $tech->officers()->attach($po);
        $music = $events['Music Festival'];

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

        // Extra sales so "Laris Manis" has a clear ranking.
        $buyers = User::factory()->withRole('user')->count(4)->create();
        foreach (['Senandung Senja Fest' => 3, 'Lari Pagi 10K Surabaya' => 2, 'Konser Akustik Pantai' => 4, 'Weekend Hardcore' => 1] as $name => $count) {
            foreach ($buyers->take($count) as $buyer) {
                $pay($buyer, $events[$name]->ticketTypes()->first(), 2);
            }
        }
    }
}
