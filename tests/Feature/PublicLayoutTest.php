<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_navbar_footer_and_login_button(): void
    {
        config(['app.name' => 'Tiketku']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Our Journey')
            ->assertSee('Hubungi Kami')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Masuk')
            ->assertSee('Your Professional Ticketing Partner')
            ->assertSee('Tiketku. Hak Cipta Dilindungi.');
    }

    public function test_buyer_menu_has_no_admin_link(): void
    {
        $this->actingAs(User::factory()->withRole('user')->create())
            ->get('/')
            ->assertSee('Tiket Saya')
            ->assertSee('Keluar')
            ->assertDontSee('Panel Admin');
    }

    public function test_staff_and_project_officer_menu_links_admin_panel(): void
    {
        $this->actingAs(User::factory()->withRole('operation')->create())->get('/')->assertSee('Panel Admin');

        $po = User::factory()->withRole('user')->create();
        Event::factory()->create()->officers()->attach($po);
        $this->actingAs($po)->get('/')->assertSee('Panel Admin');
    }
}
