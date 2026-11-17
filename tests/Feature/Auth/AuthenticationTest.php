<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('events.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_known_email_moves_to_password_step(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.identify'), ['email' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_email', $user->email);

        $this->withSession(['login_email' => $user->email])->get('/login')
            ->assertSee($user->email)
            ->assertSee('name="password"', false);
    }

    public function test_unknown_email_goes_to_register_with_email_prefilled(): void
    {
        $this->post(route('login.identify'), ['email' => 'baru@example.com'])
            ->assertRedirect(route('register'))
            ->assertSessionHasInput('email', 'baru@example.com');
    }

    public function test_invalid_email_stays_on_email_step(): void
    {
        $this->from('/login')->post(route('login.identify'), ['email' => 'bukan-email'])
            ->assertRedirect('/login')
            ->assertSessionHasErrorsIn('identify', 'email');
    }

    public function test_wrong_password_keeps_password_step(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'salah'])->assertRedirect('/login');

        $this->get('/login')->assertSee('name="password"', false);
    }

    public function test_login_returns_to_previous_page(): void
    {
        $user = User::factory()->create();

        $this->get('/login', ['referer' => url('/events/tech-conference')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(url('/events/tech-conference'));
    }

    public function test_login_shows_success_message_and_account_in_header(): void
    {
        $user = User::factory()->create(['name' => 'Rina']);

        $this->followingRedirects()
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSee('Berhasil masuk. Selamat datang, Rina!')
            ->assertSee('Menu akun Rina')
            ->assertDontSee('>Masuk</a>', false);
    }

    public function test_wrong_password_shows_indonesian_alert(): void
    {
        $user = User::factory()->create();

        $this->followingRedirects()->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSee('role="alert"', false)
            ->assertSee('Email atau password salah.');
    }

    public function test_logout_message(): void
    {
        $this->actingAs(User::factory()->create())->followingRedirects()->post('/logout')->assertSee('Kamu sudah keluar.');
    }

    public function test_checkout_header_shows_logged_in_account(): void
    {
        $event = \App\Models\Event::factory()->published()->create();

        $this->get(route('checkout.tickets', $event))->assertSee('>Masuk</a>', false);
        $this->actingAs(User::factory()->create(['name' => 'Rina']))->get(route('checkout.tickets', $event))->assertSee('Rina')->assertDontSee('>Masuk</a>', false);
    }
}
