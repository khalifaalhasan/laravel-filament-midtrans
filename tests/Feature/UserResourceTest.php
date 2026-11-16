<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_ceo_can_open_user_management(): void
    {
        $this->actingAs(User::factory()->withRole('ceo')->create())->get('/admin/users')->assertOk();
        $this->actingAs(User::factory()->withRole('operation')->create())->get('/admin/users')->assertForbidden();
    }

    public function test_ceo_can_change_role_and_assign_project_officer(): void
    {
        $this->actingAs(User::factory()->withRole('ceo')->create());
        $user = User::factory()->withRole('user')->create();
        $event = Event::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'roles' => [Role::findByName('operation')->id],
                'managedEvents' => [$event->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue($user->hasRole('operation'));
        $this->assertFalse($user->hasRole('user'));
        $this->assertTrue($user->isProjectOfficerOf($event));
    }

    public function test_edit_without_password_keeps_existing_password(): void
    {
        $this->actingAs(User::factory()->withRole('ceo')->create());
        $user = User::factory()->create();
        $hash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($hash, $user->refresh()->password);
    }
}
