@props(['title' => null, 'contained' => true])
{{-- Public (user system) layout: top bar, navbar, footer. Reference: docs/design/reference/01-beranda.png, 03-detail-event.png --}}
@php
    // shortcut: info pages (Tentang, FAQ, ...) are not built yet and link to '#'.
    $menu = [
        ['label' => 'Beranda', 'href' => route('events.index'), 'active' => request()->routeIs('events.index')],
        ['label' => 'Jelajah', 'href' => route('events.explore'), 'active' => request()->routeIs('events.explore')],
        ['label' => 'Tentang', 'href' => '#', 'active' => false],
        ['label' => 'Hubungi Kami', 'href' => '#', 'active' => false],
    ];
    $topLinks = ['Our Journey', 'Biaya', 'Tiket Gelang', 'FAQ'];
    $footerColumns = [
        'Tentang Kami' => array_fill_keys(['Tentang Kami', 'Our Journey', 'Hubungi Kami', 'Biaya'], '#'),
        'Informasi' => array_fill_keys(['Syarat & Ketentuan', 'Kebijakan Privasi & Pemrosesan Data', 'FAQ', 'Tiket Gelang', 'Validasi Tiket'], '#'),
        'Kategori Event' => [
            'Musik' => route('events.explore', ['kategori' => 'musik']),
            'Pameran' => route('events.explore', ['kategori' => 'pameran']),
            'Wahana' => route('events.explore', ['kategori' => 'wisata']),
            'Olahraga' => route('events.explore', ['kategori' => 'olahraga']),
            'Semua Kategori' => route('events.explore'),
        ],
    ];
    $socials = ['WhatsApp' => 'si-whatsapp', 'Instagram' => 'si-instagram', 'TikTok' => 'si-tiktok', 'X' => 'si-x', 'LinkedIn' => null, 'YouTube' => 'si-youtube', 'Facebook' => 'si-facebook'];
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{ $head ?? '' }}
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:text-sm">Lewati ke konten</a>

        <header x-data="{ open: false }" class="text-white print:hidden">
            <div class="bg-brand-950">
                <nav aria-label="Tautan informasi" class="mx-auto flex max-w-6xl justify-center gap-6 overflow-x-auto px-4 py-2 text-xs font-medium">
                    @foreach ($topLinks as $link)
                        <a href="#" class="whitespace-nowrap hover:underline">{{ $link }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="bg-brand-900">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4">
                    <a href="{{ route('events.index') }}" class="min-w-0 overflow-hidden text-base sm:text-xl" aria-label="{{ config('app.name') }} - Beranda">
                        <x-brand.logo />
                    </a>

                    <nav aria-label="Menu utama" class="hidden rounded-full bg-brand-800 p-1 md:flex">
                        @foreach ($menu as $item)
                            <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                                @class(['rounded-full px-4 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white',
                                    'bg-brand-600' => $item['active'], 'hover:bg-brand-700' => ! $item['active']])>{{ $item['label'] }}</a>
                        @endforeach
                    </nav>

                    <div class="flex shrink-0 items-center gap-2">
                        <span class="hidden items-center gap-1.5 rounded-full bg-brand-800 px-3 py-1.5 text-sm font-medium sm:inline-flex" title="Bahasa Indonesia">
                            <span class="h-3.5 w-3.5 overflow-hidden rounded-full bg-white" aria-hidden="true"><span class="block h-1/2 bg-red-600"></span></span>
                            ID
                        </span>
                        <a href="{{ route('events.explore') }}#cari" class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-800 hover:bg-brand-700 sm:flex focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Cari event">
                            <x-heroicon-m-magnifying-glass class="h-4 w-4" aria-hidden="true" />
                        </a>

                        @if ($user)
                            <div x-data="{ menu: false }" class="relative" x-on:keydown.escape.window="menu = false">
                                <button type="button" x-on:click="menu = !menu" x-bind:aria-expanded="menu" aria-haspopup="true"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-sm font-semibold text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-900"
                                    aria-label="Menu akun {{ $user->name }}">
                                    {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                                </button>
                                <div x-cloak x-show="menu" x-on:click.outside="menu = false" x-transition.opacity
                                    class="absolute right-0 z-40 mt-2 w-52 overflow-hidden rounded-card bg-white py-1 text-sm text-ink shadow-lg ring-1 ring-black/5">
                                    <p class="truncate border-b border-gray-100 px-4 py-2 text-xs text-gray-600">{{ $user->email }}</p>
                                    <a href="{{ route('my-tickets') }}" class="block px-4 py-2 hover:bg-brand-50">Tiket Saya</a>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-brand-50">Akun Saya</a>
                                    @if ($user->canAccessPanel(filament()->getPanel('admin')))
                                        <a href="{{ url('/admin') }}" class="block px-4 py-2 hover:bg-brand-50">Panel Admin</a>
                                    @endif
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-danger hover:bg-red-50">Keluar</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md bg-brand-700 px-4 py-2 text-sm font-medium hover:bg-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Masuk</a>
                        @endif

                        <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="mobile-menu"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-800 md:hidden" aria-label="Buka menu">
                            <x-heroicon-m-bars-3 class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <nav id="mobile-menu" x-cloak x-show="open" aria-label="Menu utama" class="border-t border-brand-800 px-4 pb-4 md:hidden">
                    @foreach ($menu as $item)
                        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                            @class(['mt-1 block rounded-md px-3 py-2 text-sm font-medium', 'bg-brand-600' => $item['active'], 'hover:bg-brand-800' => ! $item['active']])>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main id="main" @class(['flex-1', 'mx-auto w-full max-w-6xl px-4 py-8' => $contained])>
            <x-ui.flash @class(['mb-6', 'mx-auto mt-6 max-w-6xl' => ! $contained]) />
            {{ $slot }}
        </main>

        <footer class="bg-brand-900 text-white print:hidden">
            <div class="mx-auto max-w-6xl px-4 pt-12">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <x-brand.logo class="text-2xl" />
                        <p class="mt-3 text-sm font-semibold">{{ config('brand.tagline') }}</p>
                    </div>
                    @foreach ($footerColumns as $heading => $links)
                        <div>
                            <h2 class="text-sm font-semibold">{{ $heading }}</h2>
                            <ul class="mt-3 space-y-2 text-sm text-white/90">
                                @foreach ($links as $link => $href)
                                    <li><a href="{{ $href }}" class="hover:underline">{{ $link }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <div class="mt-10 flex flex-col items-start justify-between gap-4 border-t border-white/40 py-6 sm:flex-row sm:items-center">
                    <p class="text-xs">&copy; {{ now()->year }} {{ config('app.name') }}. Hak Cipta Dilindungi.</p>
                    <ul class="flex items-center gap-4">
                        @foreach ($socials as $label => $icon)
                            <li>
                                <a href="#" aria-label="{{ $label }}" class="block hover:opacity-80">
                                    @if ($icon)
                                        <x-dynamic-component :component="$icon" class="h-4 w-4 fill-current" aria-hidden="true" />
                                    @else
                                        {{-- LinkedIn is not in Simple Icons; plain "in" tile instead --}}
                                        <span class="flex h-4 w-4 items-center justify-center rounded-sm bg-white text-[9px] font-bold leading-none text-brand-900" aria-hidden="true">in</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
