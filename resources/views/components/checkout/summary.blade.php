@props(['order'])
{{-- Step 2-4 sidebar: reservation countdown + order summary. Actions go in the slot. Reference: 05-checkout-detail-pesanan.png --}}
@php($secondsLeft = max(0, (int) now()->diffInSeconds($order->expires_at, false)))
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


        {{ $slot }}
    </div>
</aside>
