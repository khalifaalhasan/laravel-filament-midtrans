<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        // Return to the page the user came from (e.g. an event) unless a guarded page already set it.
        $previous = url()->previous();
        if (! $request->session()->has('url.intended') && str_starts_with($previous, url('/')) && ! Str::contains($previous, ['/login', '/register', '/password', '/reset-password'])) {
            $request->session()->put('url.intended', $previous);
        }

        return view('auth.login');
    }

    /** Step 1: known email → ask for password; unknown → go register with the email prefilled. */
    public function identify(Request $request): RedirectResponse
    {
        $email = $request->validateWithBag('identify', ['email' => ['required', 'string', 'email', 'max:255']])['email'];

        if (User::where('email', $email)->exists()) {
            return redirect()->route('login')->with('login_email', $email);
        }

        return redirect()->route('register')->withInput(['email' => $email])
            ->with('status', 'Email belum terdaftar. Lengkapi data di bawah untuk membuat akun.');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('events.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
