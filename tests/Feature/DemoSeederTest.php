<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_from_empty_database(): void
    {
        $this->seed();

        foreach (['ceo', 'operation', 'po', 'user'] as $account) {
            $this->assertTrue(User::where('email', "{$account}@example.com")->exists());
        }
        $this->assertSame(2, User::firstWhere('email', 'user@example.com')->orders()->sole()->tickets()->count());
        $this->assertSame(1, Ticket::whereNotNull('checked_in_at')->count());
        $this->assertSame(13, Order::count());
    }
}
