{{-- Order detail (riwayat & status). No dedicated reference: panels from 05-checkout-detail-pesanan.png --}}
@php
    $event = $order->event;
    $awaiting = $order->isAwaitingPayment();
    // A pending order past its hold is effectively expired even before the scheduler marks it.
    $statusLabel = $order->status === App\Enums\OrderStatus::Pending && ! $awaiting ? 'Kedaluwarsa' : $order->status->label();
    $tone = match (true) {
        $order->status === App\Enums\OrderStatus::Paid => 'bg-brand-100 text-brand-700',
        $awaiting => 'bg-amber-100 text-amber-900',
        default => 'bg-red-50 text-danger',
    };
@endphp
<x-public-layout :title="'Pesanan '.$order->code">
    <a href="{{ route('my-tickets') }}" class="text-sm text-gray-600 hover:text-ink">&larr; Tiket Saya</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
        <div class="space-y-5">
            <div class="flex gap-4 rounded-card bg-brand-50 p-2">
                @if ($event->poster_path)
                    <img src="{{ Storage::disk('public')->url($event->poster_path) }}" alt="" class="aspect-[5/2] w-32 shrink-0 rounded-lg object-cover sm:w-48">
                @endif
                <div class="min-w-0 flex-1 rounded-lg bg-white p-3">
                    <h1 class="text-lg font-semibold">
                        <a href="{{ route('events.show', $event) }}" class="hover:underline">{{ $event->name }}</a>
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">{{ $event->dateRangeLabel() }} • {{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                    <p class="text-sm text-gray-600">{{ $event->location }}</p>
                </div>
            </div>

            @if ($order->status === App\Enums\OrderStatus::Paid)
                <x-ui.panel title="E-ticket" icon="heroicon-s-ticket">
                    <p class="mb-4 text-sm text-gray-600">Tunjukkan QR saat check-in. Satu e-ticket untuk satu orang.</p>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ($order->tickets as $ticket)
                            <li>
                                <a href="{{ route('tickets.show', $ticket) }}" class="block rounded-lg border border-gray-200 p-4 text-center hover:border-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                    <div class="mx-auto w-36" role="img" aria-label="QR tiket {{ $ticket->code }}">{!! $ticket->qrSvg(144) !!}</div>
                                    <p class="mt-2 font-mono text-sm">{{ $ticket->code }}</p>
                                    <p class="text-xs font-medium">{{ $ticket->ticketType?->name }} · {{ $ticket->holder_name ?? $order->buyer_name ?? $order->user->name }}</p>
                                    <p class="text-xs text-gray-600">{{ $ticket->checked_in_at ? 'Sudah check-in' : 'Belum check-in' }} · Lihat e-ticket</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.panel>
            @endif

            @if ($order->buyer_name)
                <x-ui.panel title="Data Pemesan" icon="heroicon-s-user">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-gray-600">Nama</dt><dd>{{ $order->buyer_name }}</dd></div>
                        <div><dt class="text-xs text-gray-600">Email</dt><dd class="break-all">{{ $order->buyer_email }}</dd></div>
                        <div><dt class="text-xs text-gray-600">No. WhatsApp</dt><dd>{{ $order->buyer_phone }}</dd></div>
                        <div><dt class="text-xs text-gray-600">Identitas</dt><dd>{{ App\Enums\IdentityType::tryFrom((string) $order->buyer_id_type)?->label() }}</dd></div>
                    </dl>
                </x-ui.panel>
            @endif
        </div>

        <aside>
            <div class="space-y-4 lg:sticky lg:top-6">
                <x-ui.panel title="Rincian Pesanan" icon="heroicon-s-shopping-bag">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Kode</dt><dd class="break-all text-right font-mono text-xs">{{ $order->code }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Status</dt><dd><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ $statusLabel }}</span></dd></div>
                        @if ($order->payment_method)
                            <div class="flex justify-between gap-4"><dt class="text-gray-600">Metode</dt><dd>{{ $order->payment_method->label() }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Dipesan</dt><dd>{{ $order->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
                        @if ($order->paid_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-600">Dibayar</dt><dd>{{ $order->paid_at->translatedFormat('d M Y, H:i') }}</dd></div>
                        @elseif ($awaiting)
                            <div class="flex justify-between gap-4"><dt class="text-gray-600">Bayar sebelum</dt><dd>{{ $order->expires_at->translatedFormat('d M Y, H:i') }}</dd></div>
                        @endif
                    </dl>
                    <div class="mt-3 space-y-3 border-t border-dashed border-gray-200 pt-3 text-sm">
                        @foreach ($order->items as $item)
                            <div>
                                <p class="font-medium">{{ $item->ticketType->name }}</p>
                                <div class="flex justify-between text-gray-600"><span>x{{ $item->quantity }}</span><span class="text-ink">{{ rupiah($item->subtotal) }}</span></div>
                            </div>
                        @endforeach
                        <div class="flex justify-between border-t border-dashed border-gray-200 pt-3"><span class="font-medium">Total Bayar</span><span class="text-base font-semibold">{{ rupiah($order->total) }}</span></div>
                    </div>
                </x-ui.panel>

                @if ($awaiting && auth()->user()->is($order->user))
                    {{-- Resumes the same Snap transaction (step 4) or the unfinished step. --}}
                    <x-ui.button :href="$order->snap_token ? route('checkout.pay', $order) : ($order->buyer_name ? route('checkout.payment', $order) : route('checkout.details', $order))" class="w-full">Lanjutkan pembayaran</x-ui.button>
                @endif
            </div>
        </aside>
    </div>
</x-public-layout>
