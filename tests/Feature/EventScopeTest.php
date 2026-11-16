<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_sees_all_events(): void
    {
        Event::factory()->count(3)->create();

        $this->assertSame(3, Event::manageableBy(User::factory()->withRole('operation')->create())->count());
    }

    public function test_project_officer_sees_only_assigned_events(): void
    {
        [$mine, $other] = Event::factory()->count(2)->create();
        $po = User::factory()->withRole('user')->create();
        $mine->officers()->attach($po);
        $other->officers()->attach(User::factory()->create());

        $this->assertEquals([$mine->id], Event::manageableBy($po)->pluck('id')->all());
    }

    public function test_regular_user_sees_nothing(): void
    {
        Event::factory()->create();

        $this->assertSame(0, Event::manageableBy(User::factory()->withRole('user')->create())->count());
    }
}
