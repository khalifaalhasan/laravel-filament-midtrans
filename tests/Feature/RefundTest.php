<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentUpdate;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $ticket = TicketType::factory()->create(['quota' => 10]);
        $ticket->forceFill(['sold' => 2])->save();
        $this->order = Order::factory()->withItem($ticket, 2)->create(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    }

    private function refundAction(): TestAction
    {
        return TestAction::make('refund')->table($this->order);
    }

    public function test_operation_can_refund_paid_order(): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('refund')->once()
            ->andReturn(new PaymentUpdate($this->order->code, OrderStatus::Refunded, $this->order->total, 'tx', 'refund')));
        $this->actingAs(User::factory()->withRole('operation')->create());

        Livewire::test(ListOrders::class)
            ->callAction($this->refundAction(), ['reason' => 'Event dibatalkan'])
            ->assertHasNoFormErrors()
            ->assertNotified('Refund berhasil');

        $this->assertSame(OrderStatus::Refunded, $this->order->refresh()->status);
        $this->assertSame(0, $this->order->items->first()->ticketType->refresh()->sold);
    }

    public function test_gateway_failure_keeps_order_paid(): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('refund')->andThrow(new PaymentGatewayException('not refundable')));
        $this->actingAs(User::factory()->withRole('ceo')->create());

        Livewire::test(ListOrders::class)
            ->callAction($this->refundAction(), ['reason' => 'x'])
            ->assertNotified('Refund gagal');

        $this->assertSame(OrderStatus::Paid, $this->order->refresh()->status);
    }

    public function test_project_officer_cannot_refund_but_sees_own_event_orders(): void
    {
        $po = User::factory()->withRole('user')->create();
        $this->order->event->officers()->attach($po);
        $otherOrder = Order::factory()->create();
        $this->actingAs($po);

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$this->order])
            ->assertCanNotSeeTableRecords([$otherOrder])
            ->assertActionHidden($this->refundAction());
    }

    public function test_reason_is_required(): void
    {
        $this->actingAs(User::factory()->withRole('ceo')->create());

        Livewire::test(ListOrders::class)
            ->callAction($this->refundAction(), ['reason' => ''])
            ->assertHasFormErrors(['reason' => 'required']);
    }
}
