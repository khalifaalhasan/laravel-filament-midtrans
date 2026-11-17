{{-- Checkout step 1: Pilih Kategori. Reference: docs/design/reference/04-checkout-pilih-tiket.png --}}
@php
    $lines = $ticketTypes->map(fn ($t) => [
        'id' => $t->id,
        'name' => $t->name,
        'price' => $t->price,
        'max' => $t->isOnSale() ? min(App\Models\Order::MAX_TICKETS, $t->availableUnits()) : 0,
    ])->values();
@endphp
<x-checkout-layout :step="1" :title="'Pilih Tiket · '.$event->name">
    <form method="POST" action="{{ route('checkout.store', $event) }}"
        x-data="{
            lines: @js($lines),
            qty: Object.fromEntries(@js($lines).map(l => [l.id, Math.min(l.max, Number((@js((object) old('quantities', [])))[l.id]) || 0)])),
            limit: {{ App\Models\Order::MAX_TICKETS }},
            get count() { return Object.values(this.qty).reduce((a, b) => a + b, 0) },
            get total() { return this.lines.reduce((sum, l) => sum + l.price * this.qty[l.id], 0) },
            canAdd(l) { return this.qty[l.id] < l.max && this.count < this.limit },
            rupiah(n) { return 'Rp' + n.toLocaleString('id-ID') },
        }"
        class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
        @csrf

        <div>
            @if ($event->poster_path)
                <img src="{{ Storage::disk('public')->url($event->poster_path) }}" alt="Poster {{ $event->name }}" class="aspect-[5/2] w-full rounded-xl object-cover">
            @endif
            <h1 class="sr-only">Pilih tiket {{ $event->name }}</h1>

            <p class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-3 py-1 text-xs font-medium text-white">
                <x-heroicon-s-ticket class="h-3.5 w-3.5" aria-hidden="true" /> Kategori Tiket
            </p>

            <ul class="mt-3 space-y-3">
                @foreach ($ticketTypes as $ticket)
                    @php($buyable = $ticket->isOnSale() && $ticket->availableUnits() > 0)
                    <li class="rounded-card bg-brand-50 p-2" x-data="{ open: true }">
                        <div class="px-1 pb-1.5">
                            @if ($buyable)
                                <x-ui.badge class="!px-1.5 !py-0 !text-[10px]">On Sale</x-ui.badge>
                            @else
                                <x-ui.badge variant="danger" class="!bg-transparent !px-1.5 !py-0 !text-[10px]">{{ $ticket->availableUnits() === 0 ? 'Sold Out' : 'Tidak Dijual' }}</x-ui.badge>
                            @endif
                        </div>

                        <div class="relative rounded-lg bg-white px-3 py-3">
                            <p class="text-sm font-medium">{{ $ticket->name }}</p>
                            @if ($ticket->isBundle())
                                <x-ui.badge variant="soft" class="mt-2 !text-[10px]"><x-heroicon-o-gift class="h-3 w-3" aria-hidden="true" />Bundle</x-ui.badge>
                            @endif
                        </div>
                        {{-- Ticket-stub divider with side notches --}}
                        <div class="relative h-px bg-white" aria-hidden="true">
                            <div class="mx-3 border-t border-dashed border-gray-100"></div>
                            <span class="absolute -left-2 -top-1.5 h-3 w-3 rounded-full bg-brand-50"></span>
                            <span class="absolute -right-2 -top-1.5 h-3 w-3 rounded-full bg-brand-50"></span>
                        </div>
                        <div class="flex min-h-[3.25rem] items-center justify-between rounded-lg bg-white px-3 py-2.5">
                            <p class="text-sm font-semibold">{{ rupiah($ticket->price) }}</p>
                            @if ($buyable)
                                <template x-if="qty[{{ $ticket->id }}] === 0">
                                    <x-ui.button class="!px-5 !py-1 !text-xs" x-on:click="if (canAdd(lines.find(l => l.id === {{ $ticket->id }}))) qty[{{ $ticket->id }}] = 1"
                                        x-bind:disabled="count >= limit">Pilih</x-ui.button>
                                </template>
                                <template x-if="qty[{{ $ticket->id }}] > 0">
                                    <div class="inline-flex items-center gap-3">
                                        <button type="button" x-on:click="qty[{{ $ticket->id }}]--" aria-label="Kurangi {{ $ticket->name }}"
                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-600 text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                                            <x-heroicon-m-minus class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <output class="min-w-[1.5rem] text-center text-sm font-medium" x-text="qty[{{ $ticket->id }}]" aria-live="polite" aria-label="Jumlah {{ $ticket->name }}"></output>
                                        <button type="button" x-on:click="if (canAdd(lines.find(l => l.id === {{ $ticket->id }}))) qty[{{ $ticket->id }}]++"
                                            x-bind:disabled="! canAdd(lines.find(l => l.id === {{ $ticket->id }}))" aria-label="Tambah {{ $ticket->name }}"
                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-600 text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:opacity-40">
                                            <x-heroicon-m-plus class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </template>
                                <input type="hidden" name="quantities[{{ $ticket->id }}]" x-bind:value="qty[{{ $ticket->id }}]" value="0">
                            @endif
                        </div>

                        @if ($ticket->description)
                            <div class="px-1 pt-2">
                                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="desc-{{ $ticket->id }}"
                                    class="flex w-full items-center justify-between text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                    Deskripsi
                                    <x-heroicon-m-chevron-up class="h-4 w-4 transition" x-bind:class="open ? '' : 'rotate-180'" aria-hidden="true" />
                                </button>
                                <p id="desc-{{ $ticket->id }}" x-show="open" class="mt-1 text-xs text-gray-600">{{ $ticket->description }}</p>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <aside>
            <div class="rounded-card bg-brand-50 p-3 lg:sticky lg:top-6">
                <h2 class="flex items-center gap-2 px-1 pb-3 text-sm font-semibold">
                    <x-heroicon-s-shopping-bag class="h-5 w-5 text-brand-600" aria-hidden="true" /> Rincian Pesanan
                </h2>
                <div class="rounded-lg bg-white p-3 text-xs">
                    <p x-show="count === 0" class="py-2 text-gray-600">Belum ada tiket dipilih.</p>
                    <template x-for="line in lines.filter(l => qty[l.id] > 0)" :key="line.id">
                        <div class="mb-3">
                            <p class="font-medium" x-text="line.name"></p>
                            <div class="mt-0.5 flex justify-between text-gray-600">
                                <span x-text="'x' + qty[line.id]"></span>
                                <span class="font-medium text-ink" x-text="rupiah(line.price * qty[line.id])"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="count > 0" class="space-y-2 border-t border-dashed border-gray-200 pt-3">
                        <div class="flex justify-between text-gray-600"><span>Subtotal</span><span class="text-ink" x-text="rupiah(total)"></span></div>
                        <div class="flex justify-between border-t border-dashed border-gray-200 pt-2 text-sm">
                            <span x-text="'Total ' + count + ' Item'"></span><span class="font-semibold" x-text="rupiah(total)"></span>
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <p role="alert" class="mt-3 rounded-md bg-red-50 px-3 py-2 text-xs text-danger">{{ $errors->first() }}</p>
                @endif
                <p class="mt-3 px-1 text-[11px] text-gray-600">Maksimal {{ App\Models\Order::MAX_TICKETS }} item dalam 1 transaksi.</p>

                @auth
                    <x-ui.button type="submit" class="mt-3 w-full" x-bind:disabled="count === 0">Beli Sekarang</x-ui.button>
                @else
                    <x-ui.button :href="route('login')" class="mt-3 w-full">Masuk untuk membeli</x-ui.button>
                @endauth
            </div>
        </aside>
    </form>
</x-checkout-layout>
