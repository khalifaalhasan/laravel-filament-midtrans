<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        $this->rememberPreviousPage($request);

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

        return redirect()->intended(route('events.index', absolute: false))
            ->with('status', 'Berhasil masuk. Selamat datang, '.$request->user()->name.'!');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Kamu sudah keluar.');
    }
}
