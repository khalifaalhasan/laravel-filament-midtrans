@props(['title' => null])
{{-- Split-screen auth layout. Reference: docs/design/reference/02-login.png --}}
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
    </head>
    <body class="bg-white font-sans text-ink antialiased">
        <div class="flex min-h-screen">
            <div class="relative hidden w-1/2 overflow-hidden bg-brand-700 lg:flex lg:items-center lg:justify-center" aria-hidden="true">
                {{-- Decorative rings from the reference --}}
                <div class="absolute -left-[30%] -top-[8%] h-[115%] w-[115%] rounded-full border-[90px] border-brand-500/70"></div>
                <div class="absolute -bottom-[45%] left-[5%] h-[95%] w-[120%] rounded-full border-[90px] border-brand-500/70"></div>
                <a href="{{ route('events.index') }}" class="relative text-6xl" tabindex="-1">
                    <x-brand.logo />
                </a>
            </div>

            <main class="flex w-full items-center justify-center px-6 py-12 lg:w-1/2">
                <div class="w-full max-w-sm">
                    <div class="mb-12 flex items-center justify-between">
                        <a href="{{ route('events.index') }}" class="text-xl" aria-label="{{ config('app.name') }} - Beranda">
                            <x-brand.logo variant="blue" />
                        </a>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-sm font-medium" title="Bahasa Indonesia">
                            <span class="h-3.5 w-3.5 overflow-hidden rounded-full bg-white ring-1 ring-gray-200" aria-hidden="true"><span class="block h-1/2 bg-red-600"></span></span>
                            ID
                        </span>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
