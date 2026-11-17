<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\PaymentUpdate;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $ceo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ceo = User::factory()->withRole('ceo')->create();
        $this->actingAs($this->ceo);
    }

    public function test_price_change_is_logged_with_old_and_new_value(): void
    {
        $ticket = TicketType::factory()->create(['price' => 100000]);
        $ticket->update(['price' => 125000]);
        $ticket->update(['quota' => 999]); // not a sensitive field

        $log = Activity::where('event', 'updated')->sole();
        $this->assertTrue($log->causer->is($this->ceo));
        $this->assertSame(100000, $log->attribute_changes['old']['price']);
        $this->assertSame(125000, $log->attribute_changes['attributes']['price']);
    }

    public function test_refund_is_logged_with_reason_and_amount(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('refund')
            ->andReturn(new PaymentUpdate($order->code, OrderStatus::Refunded, $order->total, 'tx', 'refund')));

        app(OrderService::class)->refund($order, 'Event batal');

        $log = Activity::where('event', 'refund')->sole();
        $this->assertTrue($log->subject->is($order));
        $this->assertTrue($log->causer->is($this->ceo));
        $this->assertSame('Event batal', $log->properties['reason']);
        $this->assertSame($order->total, $log->properties['amount']);
    }

    public function test_role_and_po_change_is_logged(): void
    {
        $user = User::factory()->withRole('user')->create();
        $event = Event::factory()->create(['name' => 'Expo']);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['roles' => [Role::findByName('operation')->id], 'managedEvents' => [$event->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $log = Activity::where('event', 'access_changed')->sole();
        $this->assertSame(['roles' => ['user'], 'project_officer_of' => []], $log->properties['old']);
        $this->assertSame(['roles' => ['operation'], 'project_officer_of' => ['Expo']], $log->properties['new']);
    }

    public function test_saving_without_access_change_logs_nothing(): void
    {
        $user = User::factory()->withRole('user')->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save');

        $this->assertSame(0, Activity::where('event', 'access_changed')->count());
    }

    public function test_only_ceo_can_view_audit_log(): void
    {
        TicketType::factory()->create()->update(['price' => 1]);

        Livewire::test(ListAuditLogs::class)->assertCanSeeTableRecords(Activity::all());

        $this->actingAs(User::factory()->withRole('operation')->create())
            ->get(ListAuditLogs::getUrl())
            ->assertForbidden();
    }
}
