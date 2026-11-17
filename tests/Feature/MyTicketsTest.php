<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_own_orders(): void
    {
        $user = User::factory()->create();
        $mine = Order::factory()->for($user)->create();
        $theirs = Order::factory()->create();

        $this->actingAs($user)->get(route('my-tickets'))
            ->assertOk()
            ->assertSee($mine->event->name)
            ->assertDontSee($theirs->event->name);
    }

    public function test_paid_order_page_shows_qr_tickets_to_owner_only(): void
    {
        $order = Order::factory()->withItem(\App\Models\TicketType::factory()->create(), 2)->create(['snap_token' => 'tok']);
        app(OrderService::class)->transition($order, OrderStatus::Paid);
        $code = $order->tickets()->first()->code;

        $this->actingAs($order->user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($code)
            ->assertSee('<svg', false);

        $this->actingAs(User::factory()->withRole('user')->create())
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('my-tickets'))->assertRedirect(route('login'));
    }

    public function test_active_and_history_tabs(): void
    {
        $user = User::factory()->create();
        $service = app(\App\Services\OrderService::class);
        $upcoming = \App\Models\Event::factory()->published()->create(['name' => 'Upcoming Paid Fest']);
        $past = \App\Models\Event::factory()->published()->create(['name' => 'Past Paid Fest', 'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(3)]);

        $service->transition(Order::factory()->for($user)->for($upcoming)->create(), OrderStatus::Paid);
        $service->transition(Order::factory()->for($user)->for($past)->create(), OrderStatus::Paid);
        Order::factory()->for($user)->for(\App\Models\Event::factory()->published()->create(['name' => 'Waiting Fest']))->create();
        Order::factory()->for($user)->for(\App\Models\Event::factory()->published()->create(['name' => 'Expired Fest']))->create(['status' => OrderStatus::Expired]);

        $this->actingAs($user)->get(route('my-tickets'))
            ->assertSee('Upcoming Paid Fest')->assertSee('Waiting Fest')->assertSee('Menunggu pembayaran')->assertSee('1 e-ticket')
            ->assertDontSee('Past Paid Fest')->assertDontSee('Expired Fest');

        $this->get(route('my-tickets', ['tab' => 'riwayat']))
            ->assertSee('Past Paid Fest')->assertSee('Expired Fest')->assertSee('Kedaluwarsa')
            ->assertDontSee('Upcoming Paid Fest')->assertDontSee('Waiting Fest');
    }

    public function test_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get(route('my-tickets'))->assertSee('Belum ada tiket aktif')->assertSee('Jelajah event');
    }

    public function test_order_detail_shows_items_method_status_and_resume_button(): void
    {
        $order = Order::factory()->create(['buyer_name' => 'Budi', 'buyer_email' => 'budi@contoh.com', 'payment_method' => \App\Enums\PaymentMethod::Qris, 'snap_token' => 'tok']);
        $this->mock(\App\Payments\PaymentGateway::class, fn ($m) => $m->shouldReceive('getStatus')->andReturnNull());

        $this->actingAs($order->user)->get(route('orders.show', $order))
            ->assertSee($order->code)
            ->assertSee('Menunggu pembayaran')
            ->assertSee('QRIS')
            ->assertSee('Total Bayar')
            ->assertSee('Data Pemesan')
            ->assertSee(route('checkout.pay', $order), false);

        $order->update(['expires_at' => now()->subMinute()]);
        $this->get(route('orders.show', $order))->assertSee('Kedaluwarsa')->assertDontSee('Lanjutkan pembayaran');
    }

    public function test_transaction_history_lists_all_own_orders_with_status_filter(): void
    {
        $user = User::factory()->create();
        $paid = Order::factory()->for($user)->for(\App\Models\Event::factory()->create(['name' => 'Paid Fest']))->create();
        app(\App\Services\OrderService::class)->transition($paid, OrderStatus::Paid);
        Order::factory()->for($user)->for(\App\Models\Event::factory()->create(['name' => 'Cancelled Fest']))->create(['status' => OrderStatus::Cancelled]);
        Order::factory()->for(\App\Models\Event::factory()->create(['name' => 'Someone Else Fest']))->create();

        $this->actingAs($user)->get('/')->assertSee('Riwayat Transaksi')->assertSee(route('transactions'), false);

        $this->get(route('transactions'))
            ->assertOk()
            ->assertSee('Paid Fest')->assertSee('Cancelled Fest')->assertSee('Dibatalkan')
            ->assertDontSee('Someone Else Fest')
            ->assertSee(route('orders.show', $paid), false);

        $this->get(route('transactions', ['status' => 'paid']))->assertSee('Paid Fest')->assertDontSee('Cancelled Fest');
    }
}
