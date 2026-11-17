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
            <div class="flex justify-between"><dt>Status</dt><dd>{{ $order->status->label() }}</dd></div>
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

        @if ($order->status === App\Enums\OrderStatus::Paid)
            <h2 class="text-lg font-semibold mt-8 mb-3">E-ticket</h2>
            <p class="text-sm text-gray-600 mb-4">Tunjukkan QR ini saat check-in. Satu QR untuk satu orang.</p>
            <ul class="grid gap-4 sm:grid-cols-2">
                @foreach ($order->tickets as $ticket)
                    <li class="border border-gray-200 rounded-lg p-4 text-center">
                        <div class="mx-auto w-48" role="img" aria-label="QR tiket {{ $ticket->code }}">{!! $ticket->qrSvg(192) !!}</div>
                        <p class="mt-2 font-mono text-sm">{{ $ticket->code }}</p>
                        <p class="text-xs text-gray-600">{{ $ticket->checked_in_at ? 'Sudah check-in '.$ticket->checked_in_at->translatedFormat('d M Y, H:i') : 'Belum check-in' }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-public-layout>
