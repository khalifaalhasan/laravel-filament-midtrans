<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CheckoutLayoutTest extends TestCase
{
    public function test_marks_current_step_and_shows_brand_contacts(): void
    {
        config(['app.name' => 'Tiketku', 'brand.whatsapp' => '+62 811-2222-3333', 'brand.email' => 'halo@tiketku.test']);

        $html = Blade::render('<x-checkout-layout :step="2">Isi</x-checkout-layout>');

        $this->assertSame(1, substr_count($html, 'aria-current="step"'));
        $this->assertMatchesRegularExpression('/aria-current="step"[^>]*>\s*<span[^>]*>2</', $html);
        foreach (['Pilih Kategori', 'Detail Pesanan', 'Metode Pembayaran', 'Pembayaran'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('https://wa.me/6281122223333', $html);
        $this->assertStringContainsString('halo@tiketku.test', $html);
        $this->assertStringContainsString('Tiketku. Hak Cipta Dilindungi.', $html);
    }
}
