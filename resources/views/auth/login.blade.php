{{-- Two-step login from the reference: email first, then password. Reference: docs/design/reference/02-login.png --}}
@php
    $identifyErrors = $errors->getBag('identify');
    $email = session('login_email') ?? ($identifyErrors->any() ? null : old('email'));
@endphp
<x-auth-layout title="Masuk">
    <h1 class="text-lg font-semibold">Selamat datang kembali!</h1>
    <p class="mt-1 text-sm text-gray-600">Silakan masuk untuk melanjutkan ke akun Anda.</p>

    @if (session('status'))
        <p role="status" class="mt-4 rounded-md bg-brand-50 px-3 py-2 text-sm text-brand-700">{{ session('status') }}</p>
    @endif

    @if ($email === null)
        <form method="POST" action="{{ route('login.identify') }}" class="mt-6 space-y-6"
            x-data="{ email: @js(old('email', '')) }">
            @csrf
            <div>
                <x-ui.label for="email">Email</x-ui.label>
                <input id="email" name="email" type="email" x-model="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Email"
                    @if ($identifyErrors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                    class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm placeholder:text-gray-500 focus:border-brand-600 focus:ring-brand-600">
                @if ($identifyErrors->has('email'))
                    <p id="email-error" class="mt-1 text-xs text-danger">{{ $identifyErrors->first('email') }}</p>
                @endif
            </div>
            <x-ui.button type="submit" class="w-full" x-bind:disabled="! /^\S+@\S+\.\S+$/.test(email)">
                <x-heroicon-o-arrow-right-end-on-rectangle class="h-4 w-4" aria-hidden="true" />
                Lanjutkan
            </x-ui.button>
            <p class="text-center text-sm text-gray-600">Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:underline">Daftar</a></p>
        </form>
    @else
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-6">
            @csrf
            <div>
                <x-ui.label for="email">Email</x-ui.label>
                <div class="flex items-center justify-between rounded-md border border-gray-200 bg-brand-50 px-3 py-2 text-sm">
                    <span class="truncate">{{ $email }}</span>
                    <a href="{{ route('login') }}" class="ml-3 shrink-0 font-medium text-brand-700 hover:underline">Ganti</a>
                </div>
                <input type="hidden" name="email" value="{{ $email }}">
                @error('email')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
            <x-ui.input name="password" type="password" label="Password" required autofocus autocomplete="current-password" placeholder="Password" />
            <div class="flex items-center justify-between text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600 focus:ring-brand-600">
                    Ingat saya
                </label>
                <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:underline">Lupa password?</a>
            </div>
            <x-ui.button type="submit" class="w-full">
                <x-heroicon-o-arrow-right-end-on-rectangle class="h-4 w-4" aria-hidden="true" />
                Masuk
            </x-ui.button>
        </form>
    @endif
</x-auth-layout>
