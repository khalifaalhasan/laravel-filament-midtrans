{{-- Riwayat Transaksi: every order of the buyer. No dedicated reference: panels/list style of the user system. --}}
@php
    $tone = fn ($order) => match (true) {
        $order->status === App\Enums\OrderStatus::Paid => 'bg-brand-100 text-brand-700',
        $order->isAwaitingPayment() => 'bg-amber-100 text-amber-900',
        default => 'bg-red-50 text-danger',
    };
    $label = fn ($order) => $order->status === App\Enums\OrderStatus::Pending && ! $order->isAwaitingPayment() ? 'Kedaluwarsa' : $order->status->label();
@endphp
<x-public-layout title="Riwayat Transaksi">
    <h1 class="text-xl font-semibold">Riwayat Transaksi</h1>
    <p class="mt-1 text-sm text-gray-600">Semua pembelian tiket kamu, terbaru di atas.</p>

    <nav aria-label="Filter status" class="mt-4 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
        @foreach (['' => 'Semua'] + collect(App\Enums\OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all() as $value => $text)
            @php($active = ($status?->value ?? '') === $value)
            <a href="{{ route('transactions', $value === '' ? [] : ['status' => $value]) }}" @if ($active) aria-current="page" @endif
                @class(['shrink-0 rounded-full border px-3 py-1 text-xs font-medium', 'border-brand-600 bg-brand-600 text-white' => $active, 'border-brand-600 text-brand-700 hover:bg-brand-50' => ! $active])>{{ $text }}</a>
        @endforeach
    </nav>

    @if ($orders->isEmpty())
        <div class="mt-6 rounded-card bg-brand-50 p-10 text-center">
            <x-heroicon-o-receipt-percent class="mx-auto h-10 w-10 text-brand-400" aria-hidden="true" />
            <p class="mt-3 font-medium">Belum ada transaksi</p>
            <x-ui.button :href="route('events.explore')" variant="soft" class="mt-4">Jelajah event</x-ui.button>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-card bg-brand-50 p-2">
            <ul class="divide-y divide-gray-100 rounded-lg bg-white">
                @foreach ($orders as $order)
                    <li>
                        <a href="{{ route('orders.show', $order) }}" class="grid gap-2 px-4 py-3 hover:bg-brand-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600 sm:grid-cols-[9rem_minmax(0,1fr)_auto] sm:items-center sm:gap-4">
                            <div class="text-xs text-gray-600">
                                <p class="font-medium text-ink">{{ $order->created_at->translatedFormat('d M Y') }}</p>
                                <p>{{ $order->created_at->format('H:i') }} WIB</p>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $order->event->name }}</p>
                                <p class="truncate text-xs text-gray-600">{{ $order->itemsSummary() }}{{ $order->payment_method ? ' · '.$order->payment_method->label() : '' }}</p>
                                <p class="truncate font-mono text-[11px] text-gray-500">{{ $order->code }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-end sm:gap-1">
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $tone($order) }}">{{ $label($order) }}</span>
                                <span class="text-sm font-semibold">{{ rupiah($order->total) }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-public-layout>
