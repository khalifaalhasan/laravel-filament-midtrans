{{-- "Temukan Event Menarik di Kotamu!": scrollable region cards. Reference: 01-beranda.png --}}
@php
    $href = fn ($region) => route('events.explore', ['wilayah' => $region->value]);
@endphp
<section class="mb-4" aria-labelledby="kotamu-title">
    <x-ui.section-title id="kotamu-title">Temukan Event Menarik di Kotamu!</x-ui.section-title>
    <ul class="-mx-1 flex snap-x gap-4 overflow-x-auto px-1 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach (App\Enums\Region::cases() as $region)
            <li class="w-[38%] shrink-0 snap-start sm:w-[calc(16%-0.75rem)]">
                <a href="{{ $href($region) }}" class="group block focus:outline-none" aria-label="Event di {{ $region->getLabel() }}">
                    <span class="flex aspect-square items-center justify-center rounded-2xl bg-brand-50 transition group-hover:bg-brand-100 group-focus-visible:ring-2 group-focus-visible:ring-brand-600">
                        <x-home.landmark :region="$region" class="h-3/5 w-3/5 text-brand-600" />
                    </span>
                    <span class="mt-3 flex items-center gap-1">
                        <span class="truncate rounded-full bg-brand-600 px-3 py-1 text-[10px] font-semibold uppercase text-white">{{ $region->getLabel() }}</span>
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white" aria-hidden="true">
                            <x-heroicon-m-arrow-up-right class="h-3.5 w-3.5" />
                        </span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
