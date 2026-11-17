<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_from_empty_database(): void
    {
        Storage::fake('public');
        $this->seed();

        foreach (['ceo', 'operation', 'po', 'user'] as $account) {
            $this->assertTrue(User::where('email', "{$account}@example.com")->exists());
        }
        $this->assertSame(2, User::firstWhere('email', 'user@example.com')->orders()->sole()->tickets()->count());
        $this->assertSame(1, Ticket::whereNotNull('checked_in_at')->count());
        $this->assertSame(23, Order::count());
        $this->assertSame(16, Event::published()->count());
        $this->assertSame(5, Event::where('is_featured', true)->whereNotNull('banner_path')->count());
        Storage::disk('public')->assertExists(Event::firstWhere('slug', 'jazz-di-kebun')->poster_path);
    }
}
