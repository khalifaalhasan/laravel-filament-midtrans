@php($pending = $order->status === App\Enums\OrderStatus::Pending)
<x-public-layout :title="'Pesanan '.$order->code">

    <div class="max-w-lg mx-auto bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Pesanan {{ $order->code }}</p>
        <h1 class="text-xl font-semibold mt-1">{{ $order->event->name }}</h1>

        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt>Tiket</dt><dd class="text-right">{{ $order->itemsSummary() }}</dd></div>
            <div class="flex justify-between"><dt>Total</dt><dd class="font-semibold">{{ Number::currency($order->total, 'IDR', 'id', 0) }}</dd></div>
            <div class="flex justify-between"><dt>Status</dt><dd>{{ $order->status->label() }}</dd></div>
            @if ($pending)
                <div class="flex justify-between"><dt>Bayar sebelum</dt><dd>{{ $order->expires_at->translatedFormat('d M Y, H:i') }}</dd></div>
            @endif
        </dl>

        @if ($pending && $order->isAwaitingPayment() && auth()->user()->is($order->user))
            <x-ui.button :href="$order->snap_token ? route('checkout.pay', $order) : route('checkout.details', $order)" class="mt-6 w-full">Lanjutkan pembayaran</x-ui.button>
        @endif

        @if ($order->status === App\Enums\OrderStatus::Paid)
            <h2 class="text-lg font-semibold mt-8 mb-3">E-ticket</h2>
            <p class="text-sm text-gray-600 mb-4">Tunjukkan QR ini saat check-in. Satu QR untuk satu orang.</p>
            <ul class="grid gap-4 sm:grid-cols-2">
                @foreach ($order->tickets as $ticket)
                    <li class="border border-gray-200 rounded-lg p-4 text-center">
                        <div class="mx-auto w-48" role="img" aria-label="QR tiket {{ $ticket->code }}">{!! $ticket->qrSvg(192) !!}</div>
                        <p class="mt-2 font-mono text-sm">{{ $ticket->code }}</p>
                        @if ($ticket->ticketType)
                            <p class="text-xs font-medium">{{ $ticket->ticketType->name }}</p>
                        @endif
                        <p class="text-xs text-gray-600">{{ $ticket->checked_in_at ? 'Sudah check-in '.$ticket->checked_in_at->translatedFormat('d M Y, H:i') : 'Belum check-in' }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-public-layout>
