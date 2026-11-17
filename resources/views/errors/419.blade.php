{{-- CSRF token mismatch: the form was older than the session (another tab logged in/out, or the page sat open too long). --}}
<x-public-layout title="Halaman kedaluwarsa">
    <div class="mx-auto max-w-md rounded-card bg-brand-50 p-8 text-center">
        <x-heroicon-o-clock class="mx-auto h-10 w-10 text-brand-600" aria-hidden="true" />
        <h1 class="mt-3 text-lg font-semibold">Halaman sudah kedaluwarsa</h1>
        <p class="mt-2 text-sm text-gray-600">
            Formulir ini dibuka terlalu lama, atau kamu baru saja masuk/keluar di tab lain. Muat ulang halaman lalu coba sekali lagi.
        </p>
        <div class="mt-6 flex justify-center gap-3">
            <x-ui.button :href="url()->previous() !== url()->current() ? url()->previous() : route('login')">Coba lagi</x-ui.button>
            <x-ui.button :href="route('events.index')" variant="soft">Ke beranda</x-ui.button>
        </div>
    </div>
</x-public-layout>
