{{-- Same split layout as login. Reference: docs/design/reference/02-login.png --}}
<x-auth-layout title="Lupa Password">
    <h1 class="text-lg font-semibold">Lupa password?</h1>
    <p class="mt-1 text-sm text-gray-600">Masukkan email akun Anda. Kami akan mengirim link untuk membuat password baru.</p>

    @if (session('status'))
        <p role="status" class="mt-4 rounded-md bg-brand-50 px-3 py-2 text-sm text-brand-700">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-6">
        @csrf
        <x-ui.input name="email" type="email" label="Email" required autofocus autocomplete="username" placeholder="Email" />
        <x-ui.button type="submit" class="w-full">Kirim link reset</x-ui.button>
        <p class="text-center text-sm text-gray-600"><a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Kembali ke halaman masuk</a></p>
    </form>
</x-auth-layout>
