@props(['title' => null])
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{ $head ?? '' }}
    </head>
    <body class="font-sans antialiased bg-white text-ink">
        <header class="bg-white border-b border-gray-200">
            <nav class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
                <a href="{{ route('events.index') }}" class="font-semibold">{{ config('app.name') }}</a>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('my-tickets') }}" class="text-gray-700 hover:text-gray-900">Tiket Saya</a>
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900">{{ auth()->user()->name }}</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Masuk</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-gray-900 px-3 py-2 text-white hover:bg-gray-700">Daftar</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-8">
            {{ $slot }}
        </main>
    </body>
</html>
