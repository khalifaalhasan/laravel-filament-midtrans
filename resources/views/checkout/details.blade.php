{{-- Checkout step 2: Detail Pesanan. Reference: docs/design/reference/05-checkout-detail-pesanan.png --}}
@php
    $event = $order->event;
    $person = fn (?array $p = []) => ($p ?? []) + ['name' => '', 'id_type' => '', 'id_number' => '', 'email' => '', 'phone' => ''];
    $buyer = $person(old('buyer', [
        'name' => $order->buyer_name ?? $user->name,
        'id_type' => $order->buyer_id_type,
        'id_number' => $order->buyer_id_number,
        'email' => $order->buyer_email ?? $user->email,
        'phone' => preg_replace('/^\+62/', '', (string) ($order->buyer_phone ?? $user->phone)),
    ]));
    $attendees = collect($seats)->keys()->map(fn ($i) => $person(old("attendees.$i", $order->attendees[$i] ?? [])) + ['gender' => old("attendees.$i.gender", $order->attendees[$i]['gender'] ?? '')])
        ->map(fn ($a) => ['phone' => preg_replace('/^\+62/', '', (string) $a['phone'])] + $a)->all();
    $secondsLeft = max(0, (int) now()->diffInSeconds($order->expires_at, false));
@endphp
<x-checkout-layout :step="2" :title="'Detail Pesanan · '.$event->name">
    <form method="POST" action="{{ route('checkout.details.store', $order) }}" id="details-form"
        x-data="{
            buyer: @js($buyer),
            attendees: @js($attendees),
            same: @js(array_fill(0, count($seats), false)),
            sync(i) { if (this.same[i]) { ['name', 'id_type', 'id_number', 'email', 'phone'].forEach(k => this.attendees[i][k] = this.buyer[k]) } },
        }"
        x-effect="same.forEach((on, i) => on && sync(i))"
        class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
        @csrf

        <div class="space-y-5">
            <div>
                <h1 class="text-xl font-semibold">{{ $event->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $event->dateRangeLabel() }} • {{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                <p class="text-sm text-gray-600">{{ $event->location }}</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-danger">Periksa kembali data yang ditandai. {{ $errors->first('payment') }}</div>
            @endif

            <x-ui.panel title="Data Pemesan" icon="heroicon-s-user">
                <div class="space-y-4">
                    <x-ui.input name="buyer[name]" label="Nama Lengkap" required autocomplete="name" placeholder="Masukkan nama lengkap Anda" :value="$buyer['name']" x-model="buyer.name" />
                    <div class="grid gap-3 sm:grid-cols-[2fr_3fr]">
                        <x-ui.select name="buyer[id_type]" label="Tipe Identitas" required placeholder="Pilih tipe identitas Anda" :options="App\Enums\IdentityType::options()" :value="$buyer['id_type']" x-model="buyer.id_type" />
                        <x-ui.input name="buyer[id_number]" label="Nomor Identitas" required placeholder="Masukkan nomor identitas Anda" :value="$buyer['id_number']" x-model="buyer.id_number" />
                    </div>
                    <x-ui.input name="buyer[email]" type="email" label="Email" required autocomplete="email" placeholder="Masukkan email Anda" :value="$buyer['email']" x-model="buyer.email" />
                    <x-ui.phone-input name="buyer[phone]" required placeholder="81234567890" :value="$buyer['phone']" x-model="buyer.phone" />
                </div>
            </x-ui.panel>

            @foreach ($seats as $i => $seat)
                <x-ui.panel :title="'Detail Pesanan - '.($i + 1)" icon="heroicon-s-ticket">
                    <x-slot:header>
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" class="peer sr-only" x-model="same[{{ $i }}]">
                            <span class="relative h-5 w-9 rounded-full bg-gray-200 transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-4" aria-hidden="true"></span>
                            Samakan dengan detail pemesan
                        </label>
                    </x-slot:header>

                    <p class="mb-4 rounded-md bg-brand-50 py-3 text-center text-sm font-medium">{{ $seat['label'] }}</p>
                    <div class="space-y-4">
                        <x-ui.input name="attendees[{{ $i }}][name]" label="Nama Lengkap" required placeholder="Masukkan nama lengkap Anda" :value="$attendees[$i]['name']" x-model="attendees[{{ $i }}].name" x-bind:readonly="same[{{ $i }}]" />
                        <div class="grid gap-3 sm:grid-cols-[2fr_3fr]">
                            <x-ui.select name="attendees[{{ $i }}][id_type]" label="Tipe Identitas" required placeholder="Pilih tipe identitas Anda" :options="App\Enums\IdentityType::options()" :value="$attendees[$i]['id_type']" x-model="attendees[{{ $i }}].id_type" />
                            <x-ui.input name="attendees[{{ $i }}][id_number]" label="Nomor Identitas" required placeholder="Masukkan nomor identitas Anda" :value="$attendees[$i]['id_number']" x-model="attendees[{{ $i }}].id_number" x-bind:readonly="same[{{ $i }}]" />
                        </div>
                        <x-ui.input name="attendees[{{ $i }}][email]" type="email" label="Email" required placeholder="Masukkan email Anda" :value="$attendees[$i]['email']" x-model="attendees[{{ $i }}].email" x-bind:readonly="same[{{ $i }}]" />

                        <fieldset>
                            <legend class="mb-1.5 text-sm font-medium">Jenis Kelamin<span class="text-danger" aria-hidden="true"> *</span></legend>
                            <div class="flex gap-2">
                                @foreach (['L' => ['Laki-laki', 'heroicon-o-user'], 'P' => ['Perempuan', 'heroicon-o-user']] as $value => [$label, $icon])
                                    <label class="cursor-pointer">
                                        <input type="radio" name="attendees[{{ $i }}][gender]" value="{{ $value }}" class="peer sr-only" x-model="attendees[{{ $i }}].gender" required>
                                        <span class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">
                                            <x-dynamic-component :component="$icon" class="h-4 w-4" aria-hidden="true" />{{ $label }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error("attendees.$i.gender")
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <x-ui.phone-input name="attendees[{{ $i }}][phone]" required placeholder="81234567890" :value="$attendees[$i]['phone']" x-model="attendees[{{ $i }}].phone" x-bind:readonly="same[{{ $i }}]" />
                    </div>
                </x-ui.panel>
            @endforeach
        </div>

        <aside class="space-y-4">
            <div class="lg:sticky lg:top-6 lg:space-y-4">
                <div x-data="{ left: {{ $secondsLeft }}, get mmss() { return String(Math.floor(this.left / 60)).padStart(2, '0') + ':' + String(this.left % 60).padStart(2, '0') } }"
                    x-init="const t = setInterval(() => { if (--left <= 0) { clearInterval(t); window.location.reload() } }, 1000)"
                    class="flex items-center justify-center gap-4 rounded-card bg-warning px-4 py-3 text-ink" role="timer" aria-live="off">
                    <span class="text-lg font-semibold tabular-nums" x-text="mmss">{{ gmdate('i:s', $secondsLeft) }}</span>
                    <span class="h-6 w-px bg-ink/40" aria-hidden="true"></span>
                    <span class="text-sm font-medium">Batas Waktu Tersisa</span>
                </div>

                <x-ui.panel title="Rincian Pesanan" icon="heroicon-s-shopping-bag" class="mt-4 lg:mt-0">
                    <div class="space-y-3 text-sm">
                        @foreach ($order->items as $item)
                            <div>
                                <p class="font-medium">{{ $item->ticketType->name }}</p>
                                <div class="flex justify-between text-gray-600"><span>x{{ $item->quantity }}</span><span class="text-ink">{{ rupiah($item->subtotal) }}</span></div>
                            </div>
                        @endforeach
                        <div class="flex justify-between border-t border-dashed border-gray-200 pt-3 text-gray-600"><span>Subtotal</span><span class="text-ink">{{ rupiah($order->total) }}</span></div>
                        <div class="flex justify-between border-t border-dashed border-gray-200 pt-3"><span class="font-medium">Total Bayar</span><span class="text-base font-semibold">{{ rupiah($order->total) }}</span></div>
                    </div>
                </x-ui.panel>

                <div class="mt-4 flex gap-2 lg:mt-0">
                    <button type="submit" form="cancel-form" aria-label="Kembali ke pilih kategori (reservasi dibatalkan)"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-600 text-brand-700 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        <x-heroicon-m-chevron-left class="h-4 w-4" aria-hidden="true" />
                    </button>
                    <x-ui.button type="submit" class="w-full">Lanjutkan</x-ui.button>
                </div>
            </div>
        </aside>
    </form>

    <form method="POST" action="{{ route('checkout.cancel', $order) }}" id="cancel-form" class="hidden">@csrf</form>
</x-checkout-layout>
