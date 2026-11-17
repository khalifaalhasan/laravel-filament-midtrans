<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Indonesian mobile number typed after the +62 prefix ("812...", "0812...", "62812..." all accepted). */
class IndonesianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize((string) $value) === null) {
            $fail('Nomor WhatsApp tidak valid. Contoh: 81234567890.');
        }
    }

    /** Returns "+628..." or null when it is not a plausible Indonesian mobile number. */
    public static function normalize(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value);
        $digits = preg_replace('/^(62|0)/', '', $digits);

        return preg_match('/^8\d{7,11}$/', $digits) ? '+62'.$digits : null;
    }
}
