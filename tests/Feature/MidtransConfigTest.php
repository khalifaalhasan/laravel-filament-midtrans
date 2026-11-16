<?php

namespace Tests\Feature;

use Tests\TestCase;

class MidtransConfigTest extends TestCase
{
    public function test_defaults_to_sandbox(): void
    {
        $this->assertFalse(config('midtrans.is_production'));
        $this->assertStringContainsString('sandbox', config('midtrans.snap_url'));
    }

}
