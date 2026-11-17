{{-- Same split layout as login. Reference: docs/design/reference/02-login.png --}}
<x-auth-layout title="Daftar">
    <h1 class="text-lg font-semibold">Buat akun baru</h1>
    <p class="mt-1 text-sm text-gray-600">Daftar untuk membeli tiket dan menyimpan e-ticket Anda.</p>

    @if (session('status'))
        <p role="status" class="mt-4 rounded-md bg-brand-50 px-3 py-2 text-sm text-brand-700">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <x-ui.input name="name" label="Nama Lengkap" required autofocus autocomplete="name" placeholder="Masukkan nama lengkap Anda" />
        <x-ui.input name="email" type="email" label="Email" required autocomplete="username" placeholder="Masukkan email Anda" />
        <x-ui.phone-input name="phone" required placeholder="81234567890" />
        <x-ui.input name="password" type="password" label="Password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
        <x-ui.input name="password_confirmation" type="password" label="Konfirmasi Password" required autocomplete="new-password" placeholder="Ulangi password" />

        <x-ui.button type="submit" class="!mt-6 w-full">Daftar</x-ui.button>
        <p class="text-center text-sm text-gray-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Masuk</a></p>
    </form>
</x-auth-layout>
