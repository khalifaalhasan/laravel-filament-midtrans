<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ETicketTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder(): Order
    {
        $order = Order::factory()->create(['attendees' => [['name' => 'Siti Aminah', 'gender' => 'P']]]);
        app(OrderService::class)->transition($order, OrderStatus::Paid);

        return $order;
    }

    public function test_owner_sees_printable_e_ticket_with_holder_and_qr(): void
    {
        $order = $this->paidOrder();
        $ticket = $order->tickets()->sole();

        $this->actingAs($order->user)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->code)
            ->assertSee('Siti Aminah')
            ->assertSee('<svg', false)
            ->assertSee('Belum check-in')
            ->assertSee('Cetak / Simpan PDF');

        $this->get(route('orders.show', $order))->assertSee(route('tickets.show', $ticket), false);
    }

    public function test_refunded_ticket_is_marked_invalid(): void
    {
        $order = $this->paidOrder();
        app(OrderService::class)->transition($order, OrderStatus::Refunded, [OrderStatus::Paid]);

        $this->actingAs($order->user)->get(route('tickets.show', $order->tickets()->sole()))
            ->assertSee('Tidak berlaku: pesanan dikembalikan')
            ->assertDontSee('Cetak / Simpan PDF');
    }

    public function test_other_users_cannot_open_the_e_ticket(): void
    {
        $ticket = $this->paidOrder()->tickets()->sole();

        $this->actingAs(User::factory()->withRole('user')->create())->get(route('tickets.show', $ticket))->assertForbidden();
        $this->actingAs(User::factory()->withRole('operation')->create())->get(route('tickets.show', $ticket))->assertOk();
    }
}
