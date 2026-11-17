{{-- "Kategori Event": icon circles with outlined label pills. Reference: 01-beranda.png --}}
@php
    // shortcut: Jelajah (#79) does not exist yet; links become real once the events.explore route is added.
    $href = fn ($category) => Route::has('events.explore') ? route('events.explore', ['kategori' => $category->value]) : '#';
@endphp
<section class="mb-12" aria-labelledby="kategori-title">
    <x-ui.section-title id="kategori-title">Kategori Event</x-ui.section-title>
    <ul class="grid grid-cols-4 gap-y-6 sm:grid-cols-8">
        @foreach (App\Enums\EventCategory::cases() as $category)
            <li>
                <a href="{{ $href($category) }}" class="group flex flex-col items-center focus:outline-none">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 transition group-hover:bg-brand-100">
                        <x-dynamic-component :component="$category->icon()" class="h-8 w-8 text-brand-600" aria-hidden="true" />
                    </span>
                    <span class="-mt-2 rounded-full border border-brand-600 bg-white px-3 py-0.5 text-[11px] font-medium text-brand-700 group-hover:bg-brand-50 group-focus-visible:ring-2 group-focus-visible:ring-brand-600">{{ $category->getLabel() }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
