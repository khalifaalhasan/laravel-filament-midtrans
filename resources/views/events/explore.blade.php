{{-- Jelajah. No dedicated reference: follows the card/section style of 01-beranda.png --}}
@php
    $categories = collect(App\Enums\EventCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])->all();
    $regions = collect(App\Enums\Region::cases())->mapWithKeys(fn ($r) => [$r->value => $r->getLabel()])->all();
    $active = array_filter($filters);
@endphp
<x-public-layout title="Jelajah Event">
    <h1 class="text-xl font-semibold">Jelajah Event</h1>
    <p class="mt-1 text-sm text-gray-600">Temukan event berdasarkan nama, kategori, wilayah, atau tanggal.</p>

    <form id="cari" method="GET" action="{{ route('events.explore') }}" class="mt-6 rounded-card bg-brand-50 p-4" role="search">
        <div class="grid gap-3 md:grid-cols-12">
            <div class="md:col-span-4">
                <x-ui.label for="q">Cari event</x-ui.label>
                <div class="relative">
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" aria-hidden="true" />
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Nama event"
                        class="block w-full rounded-md border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm placeholder:text-gray-500 focus:border-brand-600 focus:ring-brand-600">
                </div>
            </div>
            <div class="md:col-span-2">
                <x-ui.select name="kategori" label="Kategori" :options="['' => 'Semua'] + $categories" :value="$filters['kategori'] ?? ''" />
            </div>
            <div class="md:col-span-2">
                <x-ui.select name="wilayah" label="Wilayah" :options="['' => 'Semua'] + $regions" :value="$filters['wilayah'] ?? ''" />
            </div>
            <div class="md:col-span-2">
                <x-ui.input name="dari" type="date" label="Dari tanggal" :value="$filters['dari'] ?? null" />
            </div>
            <div class="md:col-span-2">
                <x-ui.input name="sampai" type="date" label="Sampai tanggal" :value="$filters['sampai'] ?? null" />
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
            @if ($active)
                <a href="{{ route('events.explore') }}" class="text-sm font-medium text-brand-700 hover:underline">Hapus filter</a>
            @endif
            <x-ui.button type="submit">Terapkan</x-ui.button>
        </div>
    </form>

    <nav aria-label="Filter kategori cepat" class="mt-6 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
        <a href="{{ route('events.explore', Arr::except($active, ['kategori', 'page'])) }}" @if (! isset($active['kategori'])) aria-current="page" @endif
            @class(['shrink-0 rounded-full border px-3 py-1 text-xs font-medium', 'border-brand-600 bg-brand-600 text-white' => ! isset($active['kategori']), 'border-brand-600 text-brand-700 hover:bg-brand-50' => isset($active['kategori'])])>Semua</a>
        @foreach (App\Enums\EventCategory::cases() as $category)
            @php($isActive = ($active['kategori'] ?? null) === $category->value)
            <a href="{{ route('events.explore', ['kategori' => $category->value] + Arr::except($active, ['kategori', 'page'])) }}" @if ($isActive) aria-current="page" @endif
                @class(['inline-flex shrink-0 items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium', 'border-brand-600 bg-brand-600 text-white' => $isActive, 'border-brand-600 text-brand-700 hover:bg-brand-50' => ! $isActive])>
                <x-dynamic-component :component="$category->icon()" class="h-3.5 w-3.5" aria-hidden="true" />{{ $category->getLabel() }}
            </a>
        @endforeach
    </nav>

    <p class="mt-6 text-sm text-gray-600" aria-live="polite">{{ $events->total() }} event ditemukan</p>

    @if ($events->isEmpty())
        <div class="mt-4 rounded-card bg-brand-50 p-10 text-center">
            <x-heroicon-o-calendar-days class="mx-auto h-10 w-10 text-brand-400" aria-hidden="true" />
            <p class="mt-3 font-medium">Event tidak ditemukan</p>
            <p class="mt-1 text-sm text-gray-600">Coba ubah kata kunci atau filter.</p>
        </div>
    @else
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($events as $event)
                <li><x-event-card :event="$event" class="h-full" /></li>
            @endforeach
        </ul>
        <div class="mt-8">{{ $events->links() }}</div>
    @endif
</x-public-layout>
