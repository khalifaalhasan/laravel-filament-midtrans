{{-- Same split layout as login. Reference: docs/design/reference/02-login.png --}}
<x-auth-layout title="Buat Password Baru">
    <h1 class="text-lg font-semibold">Buat password baru</h1>
    <p class="mt-1 text-sm text-gray-600">Gunakan minimal 8 karakter.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-ui.input name="email" type="email" label="Email" required autocomplete="username" :value="$request->email" />
        <x-ui.input name="password" type="password" label="Password Baru" required autofocus autocomplete="new-password" />
        <x-ui.input name="password_confirmation" type="password" label="Konfirmasi Password" required autocomplete="new-password" />
        <x-ui.button type="submit" class="!mt-6 w-full">Simpan password</x-ui.button>
    </form>
</x-auth-layout>
