<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Normally shared by the ShareErrorsFromSession middleware during a request.
        view()->share('errors', new ViewErrorBag);
    }

    public function test_button_renders_link_or_button(): void
    {
        $this->assertStringContainsString('<a href="/x"', Blade::render('<x-ui.button href="/x">Go</x-ui.button>'));
        $this->assertStringContainsString('type="submit"', Blade::render('<x-ui.button type="submit">Go</x-ui.button>'));
    }

    public function test_input_marks_required_and_links_label(): void
    {
        $html = Blade::render('<x-ui.input name="email" label="Email" required />');

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('id="email"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('text-danger', $html);
    }

    public function test_event_card_formats_price_or_label(): void
    {
        $priced = Blade::render('<x-ui.event-card href="/e" title="Fest" date="1 Des 2026" :price="250510" organizer="Org" />');
        $this->assertStringContainsString("Rp250.510", $priced);
        $this->assertStringContainsString('Org', $priced);

        $sessions = Blade::render('<x-ui.event-card href="/e" title="Fest" date="1 Des 2026" price-label="17 Sesi" />');
        $this->assertStringContainsString('17 Sesi', $sessions);
    }

    public function test_stepper_has_accessible_buttons_and_hidden_input(): void
    {
        $html = Blade::render('<x-ui.qty-stepper name="qty[1]" :value="2" :max="5" label="VIP" />');

        $this->assertStringContainsString('aria-label="Tambah VIP"', $html);
        $this->assertStringContainsString('aria-label="Kurangi VIP"', $html);
        $this->assertStringContainsString('name="qty[1]"', $html);
    }

    public function test_rupiah_helper_matches_reference_format(): void
    {
        $this->assertSame('Rp250.510', rupiah(250510));
        $this->assertSame('Rp0', rupiah(0));
        $this->assertSame('Rp1.350.000', rupiah(1350000));
    }
}
