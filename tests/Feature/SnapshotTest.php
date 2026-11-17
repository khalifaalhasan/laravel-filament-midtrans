<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\SnapshotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SnapshotSeeder::$path = storage_path('framework/testing/snapshot-'.uniqid());
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(SnapshotSeeder::$path);
        SnapshotSeeder::$path = null;
        parent::tearDown();
    }

    private function wipe(): void
    {
        foreach (array_reverse(array_keys(SnapshotSeeder::TABLES)) as $table) {
            DB::table($table)->delete();
        }
        DB::table('model_has_roles')->delete();
        Storage::fake('public');
    }

    public function test_round_trip_rebuilds_data_files_and_roles_and_anonymizes_real_accounts(): void
    {
        $this->seed();
        $real = User::factory()->withRole('user')->create(['name' => 'Nama Asli', 'email' => 'asli@gmail.test.id', 'phone' => '+6281299998888']);
        $order = Order::factory()->for($real)->create([
            'buyer_name' => 'Nama Asli', 'buyer_email' => 'asli@gmail.test.id', 'buyer_id_number' => '3201234567890001',
            'attendees' => [['name' => 'Teman Asli', 'id_number' => '3201234567890002', 'gender' => 'L']],
        ]);
        app(OrderService::class)->transition($order, OrderStatus::Paid);
        $counts = collect(SnapshotSeeder::TABLES)->keys()->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()]);
        $posterCount = count(Storage::disk('public')->allFiles());

        $this->artisan('app:export-snapshot')->assertSuccessful();
        $this->wipe();
        $this->seed(SnapshotSeeder::class);

        $this->assertEquals($counts, collect(SnapshotSeeder::TABLES)->keys()->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()]));
        $this->assertCount($posterCount, Storage::disk('public')->allFiles());
        $this->assertTrue(User::firstWhere('email', 'ceo@example.com')->hasRole('ceo'));
        $this->assertTrue(Event::firstWhere('slug', 'tech-conference')->officers()->exists());

        $anon = User::find($real->id);
        $this->assertSame("user{$real->id}@example.test", $anon->email);
        $this->assertNull($anon->phone);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $anon->password));
        $restored = Order::find($order->id);
        $this->assertNull($restored->buyer_id_number);
        $this->assertStringNotContainsString('Asli', json_encode($restored->attendees).$restored->buyer_name);
        $this->assertNull(Ticket::where('order_id', $order->id)->value('holder_id_number'));
        $this->assertStringNotContainsString('Nama Asli', File::get(SnapshotSeeder::path().'/users.json'));

        // Re-running is safe.
        $this->seed(SnapshotSeeder::class);
        $this->assertSame($counts['users'], User::count());
    }

    public function test_keep_personal_exports_real_data(): void
    {
        User::factory()->create(['name' => 'Nama Asli', 'email' => 'asli@gmail.test.id']);

        $this->artisan('app:export-snapshot', ['--keep-personal' => true])->assertSuccessful();

        $this->assertStringContainsString('asli@gmail.test.id', File::get(SnapshotSeeder::path().'/users.json'));
    }

    public function test_seeder_explains_missing_snapshot(): void
    {
        $this->expectExceptionMessage('Snapshot tidak ditemukan');

        $this->seed(SnapshotSeeder::class);
    }
}
