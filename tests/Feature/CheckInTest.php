<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\CheckIn;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $order = Order::factory()->create();
        app(OrderService::class)->transition($order, OrderStatus::Paid);
        $this->ticket = $order->tickets()->sole();
    }

    private function scan(User $as, string $code)
    {
        return Livewire::actingAs($as)->test(CheckIn::class)->fillForm(['code' => $code])->call('checkIn');
    }

    public function test_operation_checks_in_once(): void
    {
        $op = User::factory()->withRole('operation')->create();

        $this->scan($op, strtolower($this->ticket->code))->assertNotified('Check-in berhasil');
        $this->scan($op, $this->ticket->code)->assertNotified('Check-in ditolak');

        $this->assertNotNull($this->ticket->refresh()->checked_in_at);
        $this->assertSame($op->id, $this->ticket->checked_in_by);
    }

    public function test_project_officer_only_for_own_event(): void
    {
        $po = User::factory()->withRole('user')->create();
        $otherOrder = Order::factory()->create();
        $otherOrder->event->officers()->attach($po);

        $this->scan($po, $this->ticket->code)->assertNotified('Check-in ditolak');
        $this->assertNull($this->ticket->refresh()->checked_in_at);

        $this->ticket->event->officers()->attach($po);
        $this->scan($po, $this->ticket->code)->assertNotified('Check-in berhasil');
    }

    public function test_refunded_order_ticket_is_rejected(): void
    {
        app(OrderService::class)->transition($this->ticket->order, OrderStatus::Refunded, [OrderStatus::Paid]);

        $this->scan(User::factory()->withRole('ceo')->create(), $this->ticket->code)->assertNotified('Check-in ditolak');
        $this->assertNull($this->ticket->refresh()->checked_in_at);
    }

    public function test_regular_user_cannot_open_page(): void
    {
        $this->actingAs(User::factory()->withRole('user')->create())->get(CheckIn::getUrl())->assertForbidden();
    }
}
