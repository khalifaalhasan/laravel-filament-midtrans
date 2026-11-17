{{-- Tiket Saya. No dedicated reference: event-card style from 01-beranda.png --}}
@php
    $badge = fn ($order) => match ($order->status) {
        App\Enums\OrderStatus::Paid => ['brand', $order->tickets_count.' e-ticket'],
        App\Enums\OrderStatus::Pending => ['warning', 'Menunggu pembayaran'],
        default => ['danger', $order->status->label()],
    };
@endphp
<x-public-layout title="Tiket Saya">
    <h1 class="text-xl font-semibold">Tiket Saya</h1>

    <nav aria-label="Filter pesanan" class="mt-4 flex gap-1 border-b border-gray-200 text-sm">
        @foreach (['aktif' => 'Aktif', 'riwayat' => 'Riwayat'] as $key => $label)
            <a href="{{ route('my-tickets', $key === 'aktif' ? [] : ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif
                @class(['-mb-px border-b-2 px-4 py-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600',
                    'border-brand-900 font-semibold text-ink' => $tab === $key, 'border-transparent text-gray-600 hover:text-ink' => $tab !== $key])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($orders->isEmpty())
        <div class="mt-6 rounded-card bg-brand-50 p-10 text-center">
            <x-heroicon-o-ticket class="mx-auto h-10 w-10 text-brand-400" aria-hidden="true" />
            <p class="mt-3 font-medium">{{ $tab === 'aktif' ? 'Belum ada tiket aktif' : 'Belum ada riwayat pesanan' }}</p>
            <x-ui.button :href="route('events.explore')" variant="soft" class="mt-4">Jelajah event</x-ui.button>
        </div>
    @else
        <ul class="mt-6 space-y-3">
            @foreach ($orders as $order)
                @php([$tone, $text] = $badge($order))
                <li>
                    <a href="{{ route('orders.show', $order) }}" class="group flex gap-3 rounded-card bg-brand-50 p-2 transition hover:shadow-card focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 sm:gap-4">
                        <div class="aspect-[5/2] w-28 shrink-0 overflow-hidden rounded-lg bg-brand-100 sm:w-48">
                            @if ($order->event->poster_path)
                                <img src="{{ Storage::disk('public')->url($order->event->poster_path) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col justify-between gap-2 rounded-lg bg-white p-3 sm:flex-row sm:items-center">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $order->event->name }}</p>
                                <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-600">
                                    <x-heroicon-o-calendar-days class="h-4 w-4 text-brand-400" aria-hidden="true" />{{ $order->event->dateRangeLabel() }}
                                </p>
                                <p class="mt-1 truncate text-xs text-gray-600">{{ $order->itemsSummary() }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-end">
                                <span @class(['rounded-md px-2 py-0.5 text-xs font-medium',
                                    'bg-brand-100 text-brand-700' => $tone === 'brand',
                                    'bg-amber-100 text-amber-900' => $tone === 'warning',
                                    'bg-red-50 text-danger' => $tone === 'danger'])>{{ $text }}</span>
                                <span class="text-sm font-semibold">{{ rupiah($order->total) }}</span>
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-public-layout>
