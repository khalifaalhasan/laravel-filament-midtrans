<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private TicketType $regular;

    private TicketType $bundle;

    protected function setUp(): void
    {
        parent::setUp();
        $event = Event::factory()->published()->create();
        $this->regular = TicketType::factory()->for($event)->create(['name' => 'Normal - Festival', 'price' => 185000, 'quota' => 20]);
        $this->bundle = TicketType::factory()->for($event)->create(['name' => 'Bundle Duo', 'price' => 300000, 'quota' => 20, 'bundle_size' => 2]);
        $this->buyer = User::factory()->create(['name' => 'Pembeli', 'phone' => '+6281200000000']);
    }

    private function order(): Order
    {
        return app(OrderService::class)->reserve($this->buyer, [$this->regular->id => 1, $this->bundle->id => 1]);
    }

    private function person(array $overrides = []): array
    {
        return ['name' => 'Budi Santoso', 'id_type' => 'ktp', 'id_number' => '3201234567890001', 'email' => 'budi@contoh.com', 'phone' => '0812 3456 7890', ...$overrides];
    }

    public function test_page_shows_one_holder_form_per_seat_and_prefills_buyer(): void
    {
        $order = $this->order();

        $this->actingAs($this->buyer)->get(route('checkout.details', $order))
            ->assertOk()
            ->assertSee('Batas Waktu Tersisa')
            ->assertSee('Data Pemesan')
            ->assertSee('Detail Pesanan - 3')
            ->assertDontSee('Detail Pesanan - 4')
            ->assertSee('Samakan dengan detail pemesan')
            ->assertSee('Pembeli')
            ->assertSee('Total Bayar')
            ->assertSee('Rp485.000');
    }

    public function test_saves_buyer_and_holders_then_copies_holders_to_tickets_when_paid(): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('createCharge')->andReturn('tok'));
        $order = $this->order();

        $this->actingAs($this->buyer)->post(route('checkout.details.store', $order), [
            'buyer' => $this->person(),
            'attendees' => [
                $this->person(['gender' => 'L']),
                $this->person(['name' => 'Siti', 'id_type' => 'sim', 'id_number' => '123456789012', 'gender' => 'P']),
                $this->person(['name' => 'Ani', 'id_type' => 'paspor', 'id_number' => 'C1234567', 'gender' => 'P']),
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('Budi Santoso', $order->buyer_name);
        $this->assertSame('+6281234567890', $order->buyer_phone);

        app(OrderService::class)->transition($order, OrderStatus::Paid);
        $holders = $order->tickets()->orderBy('id')->get();
        $this->assertSame(['Budi Santoso', 'Siti', 'Ani'], $holders->pluck('holder_name')->all());
        $this->assertSame(['L', 'P', 'P'], $holders->pluck('holder_gender')->all());
        $this->assertSame([$this->regular->id, $this->bundle->id, $this->bundle->id], $holders->pluck('ticket_type_id')->all());
        $this->assertSame('••••4567', $holders[2]->maskedIdNumber());
    }

    public function test_identity_numbers_are_validated_per_type_and_holder_count_must_match(): void
    {
        $order = $this->order();

        $this->actingAs($this->buyer)->post(route('checkout.details.store', $order), [
            'buyer' => $this->person(['id_number' => '123']),
            'attendees' => [$this->person(['gender' => 'L'])],
        ])->assertSessionHasErrors(['buyer.id_number', 'attendees']);

        $this->post(route('checkout.details.store', $order), [
            'buyer' => $this->person(['phone' => '021555']),
            'attendees' => array_fill(0, 3, $this->person(['gender' => 'X'])),
        ])->assertSessionHasErrors(['buyer.phone', 'attendees.0.gender']);
    }

    public function test_expired_reservation_is_released_and_sent_back_to_step_one(): void
    {
        $order = $this->order();
        $order->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->buyer)->get(route('checkout.details', $order))
            ->assertRedirect(route('checkout.tickets', $order->event))
            ->assertSessionHasErrors('quantities');

        $this->assertSame(OrderStatus::Expired, $order->refresh()->status);
        $this->assertSame([0, 0], [$this->regular->refresh()->sold, $this->bundle->refresh()->sold]);
    }

    public function test_back_button_cancels_and_releases(): void
    {
        $order = $this->order();

        $this->actingAs($this->buyer)->post(route('checkout.cancel', $order))->assertRedirect(route('checkout.tickets', $order->event));

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(0, $this->bundle->refresh()->sold);
    }

    public function test_other_users_cannot_open_or_change_the_order(): void
    {
        $order = $this->order();
        $this->actingAs(User::factory()->withRole('ceo')->create());

        $this->get(route('checkout.details', $order))->assertForbidden();
        $this->post(route('checkout.cancel', $order))->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }
}
