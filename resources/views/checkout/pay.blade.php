{{-- Checkout step 4: Pembayaran. No dedicated reference yet: follows the step 2 panels (05-checkout-detail-pesanan.png).
     Snap callbacks only start polling; the status always comes from the server. --}}
@php($event = $order->event)
<x-checkout-layout :step="4" :title="'Pembayaran · '.$event->name">
    <x-slot:head>
        <script src="{{ config('midtrans.snap_url') }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
    </x-slot:head>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]"
        x-data="{
            status: 'pending',
            label: 'Menunggu pembayaran',
            checking: false,
            async check() {
                if (this.checking) return;
                this.checking = true;
                try {
                    const res = await fetch(@js(route('checkout.status', $order)), { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    this.status = data.status; this.label = data.label;
                    if (data.redirect) window.location.href = data.redirect;
                } finally { this.checking = false }
            },
            pay() {
                window.snap.pay(@js($order->snap_token), { onSuccess: () => this.check(), onPending: () => this.check(), onError: () => this.check(), onClose: () => this.check() });
            },
        }"
        x-init="setInterval(() => check(), 5000)">
        <div class="space-y-5">
            <div>
                <h1 class="text-xl font-semibold">{{ $event->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $event->dateRangeLabel() }} • {{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                <p class="text-sm text-gray-600">{{ $event->location }}</p>
            </div>

            <x-ui.panel title="Pembayaran" icon="heroicon-s-banknotes">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">Kode pesanan</dt><dd class="font-mono">{{ $order->code }}</dd></div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600">Metode</dt>
                        <dd class="flex items-center gap-2 font-medium">
                            <x-dynamic-component :component="$order->payment_method->icon()" class="h-4 w-4 text-brand-600" aria-hidden="true" />
                            {{ $order->payment_method->label() }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">Total Bayar</dt><dd class="text-base font-semibold">{{ rupiah($order->total) }}</dd></div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600">Status</dt>
                        <dd role="status" aria-live="polite" class="flex items-center gap-2 font-medium">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-warning motion-reduce:animate-none" aria-hidden="true"></span>
                            <span x-text="label">Menunggu pembayaran</span>
                        </dd>
                    </div>
                </dl>

                <x-ui.button class="mt-6 w-full" x-on:click="pay()">Bayar Sekarang</x-ui.button>
                <p class="mt-3 text-xs text-gray-600">
                    Halaman ini memeriksa status pembayaran otomatis. Setelah pembayaran terkonfirmasi, kamu akan diarahkan ke e-ticket.
                    <button type="button" class="font-medium text-brand-700 underline" x-on:click="check()">Cek sekarang</button>
                </p>
            </x-ui.panel>
        </div>

        <x-checkout.summary :order="$order" />
    </div>
</x-checkout-layout>
