<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0812-3456-7890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('events.index', absolute: false));
        $this->assertSame('+6281234567890', auth()->user()->phone);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '12345',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_register_page_prefills_email_from_login_step(): void
    {
        $this->withSession(['_old_input' => ['email' => 'baru@example.com']])
            ->get('/register')
            ->assertSee('value="baru@example.com"', false);
    }

    public function test_phone_normalization(): void
    {
        foreach (['81234567890', '081234567890', '6281234567890', '+62 812-3456-7890'] as $input) {
            $this->assertSame('+6281234567890', \App\Rules\IndonesianPhone::normalize($input));
        }
        $this->assertNull(\App\Rules\IndonesianPhone::normalize('021123456'));
    }
}
