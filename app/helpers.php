<?php

if (! function_exists('rupiah')) {
    /** Format whole rupiah like the design reference: "Rp250.510" (no space, dot thousands). */
    function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
