@php($pending = $order->status === App\Enums\OrderStatus::Pending)
<x-public-layout :title="'Pesanan '.$order->code">
    @if ($pending && $order->snap_token)
        <x-slot:head>
            <script src="{{ config('midtrans.snap_url') }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
        </x-slot:head>
    @endif

    <div class="max-w-lg mx-auto bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Pesanan {{ $order->code }}</p>
        <h1 class="text-xl font-semibold mt-1">{{ $order->event->name }}</h1>

        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt>Tiket</dt><dd>{{ $order->ticketType->name }} × {{ $order->quantity }}</dd></div>
            <div class="flex justify-between"><dt>Total</dt><dd class="font-semibold">{{ Number::currency($order->total, 'IDR', 'id', 0) }}</dd></div>
            <div class="flex justify-between"><dt>Status</dt><dd data-testid="order-status">{{ match ($order->status) {
                App\Enums\OrderStatus::Pending => 'Menunggu pembayaran',
                App\Enums\OrderStatus::Paid => 'Lunas',
                App\Enums\OrderStatus::Expired => 'Kedaluwarsa',
                App\Enums\OrderStatus::Cancelled => 'Dibatalkan',
                App\Enums\OrderStatus::Refunded => 'Dikembalikan',
            } }}</dd></div>
            @if ($pending)
                <div class="flex justify-between"><dt>Bayar sebelum</dt><dd>{{ $order->expires_at->translatedFormat('d M Y, H:i') }}</dd></div>
            @endif
        </dl>

        @if ($pending && $order->snap_token)
            <x-primary-button id="pay-button" class="mt-6 w-full justify-center">Bayar sekarang</x-primary-button>
            <script>
                document.getElementById('pay-button').addEventListener('click', function () {
                    // Snap callbacks only refresh the page; the status itself comes from the server.
                    const reload = () => window.location.reload();
                    window.snap.pay(@js($order->snap_token), { onSuccess: reload, onPending: reload, onError: reload, onClose: reload });
                });
            </script>
        @endif
    </div>
</x-public-layout>
