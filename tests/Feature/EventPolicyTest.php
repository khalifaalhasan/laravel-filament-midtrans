<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_officer_can_only_update_assigned_event(): void
    {
        [$eventA, $eventB] = Event::factory()->count(2)->create();
        $po = User::factory()->withRole('user')->create();
        $eventA->officers()->attach($po);

        $this->assertTrue($po->can('update', $eventA));
        $this->assertFalse($po->can('update', $eventB));
        $this->assertFalse($po->can('delete', $eventA));
        $this->assertFalse($po->can('create', Event::class));
    }

    public function test_staff_can_update_any_event_but_only_ceo_deletes(): void
    {
        $event = Event::factory()->create();
        $ceo = User::factory()->withRole('ceo')->create();
        $operation = User::factory()->withRole('operation')->create();

        $this->assertTrue($ceo->can('update', $event));
        $this->assertTrue($operation->can('update', $event));
        $this->assertTrue($ceo->can('delete', $event));
        $this->assertFalse($operation->can('delete', $event));
        $this->assertFalse($operation->can('assignOfficers', $event));
    }

    public function test_regular_user_has_no_event_access(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->withRole('user')->create();

        $this->assertFalse($user->can('viewAny', Event::class));
        $this->assertFalse($user->can('update', $event));
    }

    public function test_project_officer_can_access_panel(): void
    {
        $po = User::factory()->withRole('user')->create();
        Event::factory()->create()->officers()->attach($po);

        $this->actingAs($po)->get('/admin')->assertOk();
    }
}
