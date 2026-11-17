<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BrandLogoTest extends TestCase
{
    public function test_logo_uses_app_name_in_both_variants(): void
    {
        config(['app.name' => 'Tiketku']);

        foreach (['white' => 'text-white', 'blue' => 'bg-brand-900'] as $variant => $class) {
            $html = Blade::render('<x-brand.logo :variant="$v" />', ['v' => $variant]);
            $this->assertStringContainsString('Tiketku', $html);
            $this->assertStringContainsString($class, $html);
            $this->assertStringContainsString('<svg', $html);
        }
    }
}
