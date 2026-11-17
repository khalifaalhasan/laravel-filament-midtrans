@props(['step' => 1, 'title' => null])
{{-- Checkout layout: white header, navy 4-step bar, slim footer. Reference: docs/design/reference/04-*.png, 05-*.png --}}
@php
    $steps = [1 => 'Pilih Kategori', 2 => 'Detail Pesanan', 3 => 'Metode Pembayaran', 4 => 'Pembayaran'];
    $whatsapp = config('brand.whatsapp');
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
        <header>
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <a href="{{ route('events.index') }}" class="text-xl" aria-label="{{ config('app.name') }} - Beranda">
                    <x-brand.logo variant="blue" />
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-sm font-medium" title="Bahasa Indonesia">
                    <span class="h-3.5 w-3.5 overflow-hidden rounded-full bg-white ring-1 ring-gray-200" aria-hidden="true"><span class="block h-1/2 bg-red-600"></span></span>
                    ID
                </span>
            </div>

            <nav aria-label="Langkah checkout" class="bg-brand-900 text-white">
                <ol class="mx-auto flex max-w-6xl items-center justify-start gap-2 overflow-x-auto px-4 py-2.5 text-sm sm:justify-center">
                    @foreach ($steps as $number => $label)
                        <li class="flex shrink-0 items-center gap-2">
                            <span @if ($number === (int) $step) aria-current="step" @endif class="flex items-center gap-2">
                                <span @class([
                                    'flex h-5 w-5 items-center justify-center rounded-full text-xs font-semibold',
                                    'bg-white text-brand-900' => $number === (int) $step,
                                    'border border-white/80 text-white/90' => $number !== (int) $step,
                                ])>{{ $number }}</span>
                                <span @class([
                                    'font-semibold' => $number === (int) $step,
                                    'hidden text-white/90 sm:inline' => $number !== (int) $step,
                                ])>{{ $label }}</span>
                            </span>
                            @if (! $loop->last)
                                <x-heroicon-m-chevron-right class="h-4 w-4 text-white/80" aria-hidden="true" />
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        </header>

        <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
            {{ $slot }}
        </main>

        <footer class="mx-auto w-full max-w-6xl px-4 pt-10">
            <div class="flex flex-col items-center gap-5">
                <x-brand.logo variant="blue" class="text-xl" />
                <ul class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-medium">
                    <li>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="inline-flex items-center gap-2 hover:underline">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-white" aria-hidden="true"><x-si-whatsapp class="h-3 w-3 fill-current" /></span>
                            <span><span class="sr-only">WhatsApp </span>{{ $whatsapp }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ config('brand.email') }}" class="inline-flex items-center gap-2 hover:underline">
                            <x-heroicon-s-envelope class="h-5 w-5 text-brand-600" aria-hidden="true" />
                            <span><span class="sr-only">Email </span>{{ config('brand.email') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
            <p class="mt-6 border-t border-gray-200 py-6 text-center text-xs text-gray-600">&copy; {{ now()->year }} {{ config('app.name') }}. Hak Cipta Dilindungi.</p>
        </footer>
    </body>
</html>
