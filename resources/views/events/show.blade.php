{{-- Event detail. Reference: docs/design/reference/03-detail-event.png --}}
@php
    $url = fn (?string $path) => $path ? Storage::disk('public')->url($path) : null;
    $sameDay = $event->starts_at->isSameDay($event->ends_at);
    $tabs = array_filter([
        'deskripsi' => $event->description ? 'Deskripsi' : null,
        'syarat' => $event->terms ? 'Syarat & Ketentuan' : null,
        'fasilitas' => $event->facilities?->isNotEmpty() ? 'Fasilitas' : null,
    ]);
    // shortcut: "Pesan Sekarang" jumps to the ticket list below until checkout step 1 (#85) exists.
    $orderHref = '#tiket';
@endphp
<x-public-layout :title="$event->name">
    <div class="grid gap-10 pb-24 lg:grid-cols-[minmax(0,1fr)_360px] lg:pb-0">
        <div class="order-2 lg:order-1">
            <x-ui.badge>
                <x-dynamic-component :component="$event->category->icon()" class="h-3.5 w-3.5" aria-hidden="true" />
                {{ $event->category->getLabel() }}
            </x-ui.badge>
            <h1 class="mt-2 text-xl font-semibold sm:text-2xl">{{ $event->name }}</h1>

            @if ($event->organizer_name)
                <p class="mt-5 text-xs font-medium text-gray-600">Penyelenggara</p>
                <div class="mt-2 flex items-center gap-2.5">
                    @if ($event->organizer_logo_path)
                        <img src="{{ $url($event->organizer_logo_path) }}" alt="" class="h-8 w-8 rounded-full object-cover">
                    @endif
                    <span class="text-sm font-medium">{{ $event->organizer_name }}</span>
                </div>
            @endif

            @if ($event->instagram_url || $event->tiktok_url)
                <p class="mt-5 text-xs font-medium text-gray-600">Media Sosial</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (['Instagram' => [$event->instagram_url, 'si-instagram'], 'TikTok' => [$event->tiktok_url, 'si-tiktok']] as $label => [$link, $icon])
                        @if ($link)
                            <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full bg-brand-50 py-1 pl-1 pr-3 text-sm font-medium text-brand-700 hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-600 text-white"><x-dynamic-component :component="$icon" class="h-3.5 w-3.5 fill-current" aria-hidden="true" /></span>
                                {{ $label }}<span class="sr-only"> (tab baru)</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($tabs)
                <nav aria-label="Bagian halaman" class="mt-6 flex gap-1 border-b border-gray-200 text-sm" x-data="{ active: @js(array_key_first($tabs)) }">
                    @foreach ($tabs as $id => $label)
                        <a href="#{{ $id }}" x-on:click="active = @js($id)"
                            :class="active === @js($id) ? 'border-brand-900 font-semibold text-ink' : 'border-transparent text-gray-600 hover:text-ink'"
                            class="-mb-px border-b-2 px-3 py-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($event->description)
                <section id="deskripsi" class="scroll-mt-6 pt-6">
                    <h2 class="mb-3 text-lg font-semibold">Deskripsi</h2>
                    <div class="rich-text">{!! $event->richHtml('description') !!}</div>
                </section>
            @endif

            @if ($event->terms)
                <section id="syarat" class="scroll-mt-6 pt-6">
                    <h2 class="mb-3 text-lg font-semibold">Syarat &amp; Ketentuan</h2>
                    <div class="rich-text">{!! $event->richHtml('terms') !!}</div>
                </section>
            @endif

            @if ($event->facilities?->isNotEmpty())
                <section id="fasilitas" class="scroll-mt-6 pt-6">
                    <h2 class="mb-4 text-lg font-semibold">Fasilitas Event</h2>
                    <ul class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        @foreach ($event->facilities as $facility)
                            <li class="flex items-center gap-3">
                                <x-dynamic-component :component="$facility->icon()" class="h-6 w-6 text-brand-600" aria-hidden="true" />
                                {{ $facility->getLabel() }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section id="tiket" class="scroll-mt-6 pt-10">
                <h2 class="mb-3 text-lg font-semibold">Tiket</h2>
                @if ($errors->any())
                    <div role="alert" class="mb-3 rounded-md bg-red-50 p-3 text-sm text-danger">{{ $errors->first() }}</div>
                @endif
                @forelse ($event->ticketTypes as $ticket)
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-card bg-brand-50 p-4">
                        <div>
                            <p class="font-medium">{{ $ticket->name }}</p>
                            @if ($ticket->isBundle())
                                <x-ui.badge variant="soft" class="mt-1"><x-heroicon-o-gift class="h-3.5 w-3.5" aria-hidden="true" />Bundle {{ $ticket->bundle_size }} tiket</x-ui.badge>
                            @endif
                            <p class="text-sm text-gray-600">
                                @if ($ticket->availableUnits() === 0)
                                    <x-ui.badge variant="danger">Sold Out</x-ui.badge>
                                @elseif (! $ticket->isOnSale())
                                    {{ $ticket->sale_starts_at?->isFuture() ? 'Dijual mulai '.$ticket->sale_starts_at->translatedFormat('d M Y, H:i') : 'Penjualan ditutup' }}
                                @else
                                    Sisa {{ $ticket->availableUnits() }} {{ $ticket->isBundle() ? 'paket' : 'tiket' }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <p class="font-semibold">{{ rupiah($ticket->price) }}</p>
                            @if ($ticket->isOnSale() && $ticket->availableUnits() > 0)
                                @auth
                                    <form method="POST" action="{{ route('checkout.store', $event) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="ticket_type_id" value="{{ $ticket->id }}">
                                        <label for="qty-{{ $ticket->id }}" class="sr-only">Jumlah {{ $ticket->name }}</label>
                                        <select id="qty-{{ $ticket->id }}" name="quantity" class="rounded-md border-gray-200 text-sm">
                                            @foreach (range(1, min(App\Models\Order::MAX_TICKETS, $ticket->availableUnits())) as $n)
                                                <option value="{{ $n }}">{{ $n }}</option>
                                            @endforeach
                                        </select>
                                        <x-ui.button type="submit">Beli</x-ui.button>
                                    </form>
                                @else
                                    <x-ui.button :href="route('login')" variant="outline">Masuk untuk membeli</x-ui.button>
                                @endauth
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Belum ada tiket.</p>
                @endforelse
            </section>
        </div>

        <aside class="order-1 lg:order-2">
            <div class="space-y-6 lg:sticky lg:top-6">
                @if ($event->poster_path)
                    <img src="{{ $url($event->poster_path) }}" alt="Poster {{ $event->name }}" class="aspect-[5/2] w-full rounded-xl object-cover">
                @endif

                <section aria-labelledby="detail-acara">
                    <h2 id="detail-acara" class="text-base font-semibold">Detail Acara</h2>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex items-center gap-2.5">
                            <dt><x-heroicon-s-calendar-days class="h-5 w-5 text-brand-400" /><span class="sr-only">Tanggal</span></dt>
                            <dd>{{ $event->dateRangeLabel() }}</dd>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <dt><x-heroicon-s-clock class="h-5 w-5 text-brand-400" /><span class="sr-only">Waktu</span></dt>
                            <dd>{{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}{{ $sameDay ? '' : ' (setiap hari)' }}</dd>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <dt><x-heroicon-s-map-pin class="h-5 w-5 text-brand-400" /><span class="sr-only">Lokasi</span></dt>
                            <dd>{{ $event->location }}</dd>
                        </div>
                    </dl>
                </section>

                {{-- Price card: in the sidebar on desktop, a fixed bottom bar on phones --}}
                <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-4 border-t border-gray-200 bg-white px-4 py-3 shadow-card lg:static lg:rounded-card lg:border-0 lg:px-4 lg:py-4 lg:ring-1 lg:ring-black/5">
                    <div>
                        <p class="text-xs text-gray-600">Harga mulai dari</p>
                        <p class="text-base font-semibold">{{ $startingPrice !== null ? rupiah($startingPrice) : 'Tiket habis' }}</p>
                    </div>
                    @if ($canBuy)
                        <x-ui.button :href="$orderHref">Pesan Sekarang</x-ui.button>
                    @else
                        <x-ui.button disabled aria-disabled="true">Pesan Sekarang</x-ui.button>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</x-public-layout>
