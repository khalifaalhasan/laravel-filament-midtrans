<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller
{
    /** Remember the page the visitor came from so login/register can send them back there. */
    protected function rememberPreviousPage(Request $request): void
    {
        $previous = url()->previous();

        if (! $request->session()->has('url.intended')
            && str_starts_with($previous, url('/'))
            && ! Str::contains($previous, ['/login', '/register', '/forgot-password', '/reset-password'])) {
            $request->session()->put('url.intended', $previous);
        }
    }
}
