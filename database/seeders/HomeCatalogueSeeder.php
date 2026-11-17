<?php

namespace Database\Seeders;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\Facility;
use App\Enums\OrderStatus;
use App\Enums\Region;
use App\Models\Event;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo catalogue for the home page (banners, categories, regions, best sellers).
 * Safe to re-run on a database that already has data: events match by slug, tickets by name,
 * and best-seller sales are only added to events that have no paid orders yet.
 *
 *   php artisan db:seed --class=HomeCatalogueSeeder
 */
class HomeCatalogueSeeder extends Seeder
{
    // [name, category, region, organizer, days from now, duration days, featured, tickets [name => [price, quota, bundle_size?]]]
    public const CATALOGUE = [
        ['Tech Conference', EventCategory::Talkshow, Region::Jabodetabek, 'Inovasi Nusantara', 20, 1, true, ['Regular' => [150_000, 100], 'VIP' => [500_000, 20]]],
        ['Music Festival', EventCategory::Musik, Region::DiyJateng, 'Nara Kreatif', 35, 2, true, ['Festival Pass' => [250_000, 500], 'Bundling 4 Festival Pass (Bundle 4 Tiket)' => [900_000, 200, 4], 'Bundling 10 Festival Pass (Bundle 10 Tiket)' => [2_000_000, 200, 10]]],
        ['Senandung Senja Fest', EventCategory::Musik, Region::JawaBarat, 'Senja Production', 12, 1, true, ['Reguler' => [75_000, 200], 'VIP' => [150_000, 200], 'Bundling 2 Reguler (Bundle 2 Tiket)' => [140_000, 100, 2]]],
        ['Pameran Seni Rupa Nusantara', EventCategory::Pameran, Region::Jabodetabek, 'Galeri Kita', 5, 14, false, ['Reguler' => [40_000, 200]]],
        ['Teater Bayang Kota', EventCategory::Teater, Region::DiyJateng, 'Sanggar Bayang', 18, 1, false, ['Reguler' => [55_000, 200], 'VIP' => [90_000, 200]]],
        ['Lari Pagi 10K Surabaya', EventCategory::Olahraga, Region::JawaTimur, 'Komunitas Lari Timur', 26, 1, true, ['Reguler' => [150_000, 200]]],
        ['Jelajah Danau Toba', EventCategory::Wisata, Region::Sumatera, 'Toba Trip', 40, 3, false, ['Reguler' => [350_000, 200]]],
        ['Talkshow Kreator Digital', EventCategory::Talkshow, Region::Jabodetabek, 'Ruang Kreator', 8, 1, false, ['Reguler' => [25_000, 200]]],
        ['Workshop Manual Brew', EventCategory::Workshop, Region::JawaBarat, 'Kopi Pagi', 15, 1, false, ['Reguler' => [120_000, 200]]],
        ['Turnamen E-Sport Kampus', EventCategory::Kompetisi, Region::Sulawesi, 'Liga Mahasiswa', 30, 2, false, ['Reguler' => [30_000, 200]]],
        ['Festival Budaya Borneo', EventCategory::Pameran, Region::Kalimantan, 'Borneo Kreatif', 45, 3, false, ['Reguler' => [50_000, 200]]],
        ['Konser Akustik Pantai', EventCategory::Musik, Region::BaliNusra, 'Ombak Musik', 22, 1, true, ['Reguler' => [99_900, 200], 'VIP' => [199_000, 200]]],
        ['Weekend Hardcore', EventCategory::Musik, Region::DiyJateng, 'Kota Hardcore', 3, 1, false, ['Reguler' => [40_000, 200]]],
        ['Expo Otomotif Jatim', EventCategory::Pameran, Region::JawaTimur, 'Garasi Expo', 10, 3, false, ['Reguler' => [25_000, 200]]],
        ['Jazz di Kebun', EventCategory::Musik, Region::JawaBarat, 'Kebun Jazz', 28, 2, false, ['Reguler' => [94_000, 200], 'VIP' => [250_000, 200]]],
        ['Lomba Robotik Pelajar', EventCategory::Kompetisi, Region::Jabodetabek, 'Robo Edu', 50, 1, false, ['Reguler' => [35_000, 200]]],
    ];

    /** Paid orders (2 tickets each) added so "Laris Manis" has a clear ranking. */
    public const BEST_SELLERS = ['Konser Akustik Pantai' => 4, 'Senandung Senja Fest' => 3, 'Lari Pagi 10K Surabaya' => 2, 'Weekend Hardcore' => 1];

    private const TERMS = '<ol><li>Tiket resmi hanya dijual melalui platform ini.</li><li>Satu e-ticket berlaku untuk satu orang.</li><li>Maksimal pembelian 5 tiket dalam 1 transaksi.</li><li>Tiket tidak dapat dialihkan atau dikembalikan, kecuali event dibatalkan penyelenggara.</li><li>Wajib membawa e-ticket dan kartu identitas asli (KTP/SIM/Paspor) saat penukaran.</li><li>Dilarang membawa senjata tajam, minuman beralkohol, dan obat-obatan terlarang.</li></ol><p><strong>Pembelian tiket berarti pembeli telah membaca dan menyetujui syarat &amp; ketentuan ini.</strong></p>';

    public function run(OrderService $orders): void
    {
        foreach (self::CATALOGUE as $i => [$name, $category, $region, $organizer, $inDays, $days, $featured, $tickets]) {
            $startsAt = now()->addDays($inDays)->setTime(14, 0);
            $event = Event::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'description' => "<p><strong><em>{$name}</em></strong></p><p>{$name} hadir untuk kamu bersama {$organizer}. Siapkan dirimu untuk pengalaman yang seru, aman, dan berkesan. Info lengkap, jadwal, dan tiket tersedia di halaman ini.</p>",
                'terms' => self::TERMS,
                'facilities' => array_slice(Facility::cases(), 0, 3 + $i % 5),
                'instagram_url' => 'https://instagram.com/'.Str::slug($organizer, ''),
                'tiktok_url' => $i % 2 ? null : 'https://tiktok.com/@'.Str::slug($organizer, ''),
                'location' => $region->getLabel(),
                'status' => EventStatus::Published,
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

            foreach ($tickets as $ticketName => $spec) {
                [$price, $quota, $bundle] = $spec + [2 => 1];
                $event->ticketTypes()->firstOrCreate(['name' => $ticketName], [
                    'price' => $price,
                    'quota' => $quota,
                    'bundle_size' => $bundle,
                    'description' => $bundle > 1 ? "Paket {$bundle} tiket sekaligus." : null,
                ]);
            }
        }

        foreach (self::BEST_SELLERS as $name => $count) {
            $event = Event::firstWhere('slug', Str::slug($name));
            if ($event->orders()->where('status', OrderStatus::Paid)->exists()) {
                continue;
            }
            foreach (User::factory()->withRole('user')->count($count)->create() as $buyer) {
                $order = $orders->reserve($buyer, [$event->ticketTypes()->orderBy('id')->value('id') => 2]);
                $orders->transition($order, OrderStatus::Paid, attributes: ['paid_at' => now(), 'payment_type' => 'qris']);
            }
        }
    }
}
