{{-- Checkout step 3: Metode Pembayaran. No dedicated reference yet: follows the step 2 panels (05-checkout-detail-pesanan.png). --}}
@php($event = $order->event)
<x-checkout-layout :step="3" :title="'Metode Pembayaran · '.$event->name">
    <form method="POST" action="{{ route('checkout.payment.store', $order) }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]"
        x-data="{ method: @js(old('payment_method', $order->payment_method?->value)) }">
        @csrf

        <div class="space-y-5">
            <div>
                <h1 class="text-xl font-semibold">{{ $event->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $event->dateRangeLabel() }} • {{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                <p class="text-sm text-gray-600">{{ $event->location }}</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
            @endif

            <x-ui.panel title="Pilih Metode Pembayaran" icon="heroicon-s-credit-card">
                <fieldset>
                    <legend class="sr-only">Metode pembayaran</legend>
                    <div class="space-y-3">
                        @foreach (App\Enums\PaymentMethod::cases() as $method)
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $method->value }}" class="peer sr-only" x-model="method" required>
                                <span class="flex items-start gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-brand-300 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                        <x-dynamic-component :component="$method->icon()" class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold">{{ $method->label() }}</span>
                                        <span class="block text-xs text-gray-600">{{ $method->description() }}</span>
                                        <span class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($method->brands() as $brand)
                                                <span class="rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-gray-700">{{ $brand }}</span>
                                            @endforeach
                                        </span>
                                    </span>
                                    <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-gray-300" aria-hidden="true"
                                        :class="method === @js($method->value) ? 'border-brand-600' : ''">
                                        <span class="h-2 w-2 rounded-full bg-brand-600" x-show="method === @js($method->value)"></span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @if ($order->snap_token)
                    <p role="note" class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        Pembayaran dengan {{ $order->payment_method->label() }} sudah dibuat. Memilih metode lain akan membatalkan instruksi pembayaran sebelumnya.
                        Jangan membayar dengan instruksi lama setelah mengganti metode.
                    </p>
                @else
                    <p class="mt-4 text-xs text-gray-600">Instruksi pembayaran muncul di langkah berikutnya.</p>
                @endif
            </x-ui.panel>
        </div>

        <x-checkout.summary :order="$order">
            <div class="mt-4 flex gap-2 lg:mt-0">
                <a href="{{ route('checkout.details', $order) }}" aria-label="Kembali ke detail pesanan"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-600 text-brand-700 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                    <x-heroicon-m-chevron-left class="h-4 w-4" aria-hidden="true" />
                </a>
                <x-ui.button type="submit" class="w-full" x-bind:disabled="! method">Lanjutkan</x-ui.button>
            </div>
        </x-checkout.summary>
    </form>
</x-checkout-layout>
