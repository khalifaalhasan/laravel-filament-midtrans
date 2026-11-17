{{-- Payment confirmed by the gateway. Stays on screen until the buyer chooses where to go. --}}
@php($event = $order->event)
<x-checkout-layout :step="4" :title="'Pembayaran Berhasil · '.$event->name">
    <div class="mx-auto max-w-lg">
        <div class="rounded-card bg-brand-50 p-2">
            <div class="rounded-lg bg-white px-6 py-8 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <x-heroicon-s-check-circle class="h-10 w-10 text-green-600" aria-hidden="true" />
                </span>
                <h1 class="mt-4 text-xl font-semibold">Pembayaran Berhasil!</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Terima kasih{{ $order->buyer_name ? ', '.$order->buyer_name : '' }}. Pembayaranmu sudah kami terima dan
                    {{ $order->tickets_count }} e-ticket untuk <span class="font-medium text-ink">{{ $event->name }}</span> sudah siap.
                </p>

                <dl class="mt-6 space-y-2 border-t border-dashed border-gray-200 pt-4 text-left text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">Kode pesanan</dt><dd class="break-all text-right font-mono text-xs">{{ $order->code }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">Tiket</dt><dd class="text-right">{{ $order->itemsSummary() }}</dd></div>
                    @if ($order->payment_method)
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Metode</dt><dd>{{ $order->payment_method->label() }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">Dibayar</dt><dd>{{ $order->paid_at?->translatedFormat('d M Y, H:i') }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-dashed border-gray-200 pt-2"><dt class="font-medium">Total Bayar</dt><dd class="text-base font-semibold">{{ rupiah($order->total) }}</dd></div>
                </dl>

                <div class="mt-6 space-y-2">
                    <x-ui.button :href="route('orders.show', $order)" class="w-full">
                        <x-heroicon-s-ticket class="h-4 w-4" aria-hidden="true" /> Lihat E-ticket
                    </x-ui.button>
                    <x-ui.button :href="route('my-tickets')" variant="soft" class="w-full">Tiket Saya</x-ui.button>
                </div>
                <p class="mt-4 text-xs text-gray-600">Tunjukkan QR e-ticket dan kartu identitas asli saat check-in.</p>
            </div>
        </div>
    </div>
</x-checkout-layout>
