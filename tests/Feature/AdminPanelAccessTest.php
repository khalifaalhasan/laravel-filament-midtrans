<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_access_panel(string $role): void
    {
        $this->actingAs(User::factory()->withRole($role)->create())->get('/admin')->assertOk();
    }

    public static function staffRoles(): array
    {
        return [['ceo'], ['operation']];
    }

    public function test_regular_user_cannot_access_panel(): void
    {
        $this->actingAs(User::factory()->withRole('user')->create())->get('/admin')->assertForbidden();
    }

    public function test_registered_user_gets_user_role(): void
    {
        $this->post('/register', [
            'name' => 'New',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(User::where('email', 'new@example.com')->first()->hasRole('user'));
    }
}
