<x-public-layout title="Tiket Saya">
    <h1 class="text-2xl font-semibold mb-6">Tiket Saya</h1>

    @if ($orders->isEmpty())
        <p class="text-gray-600">Belum ada pesanan. <a href="{{ route('events.index') }}" class="underline">Lihat event</a>.</p>
    @else
        <ul class="space-y-3">
            @foreach ($orders as $order)
                <li>
                    <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between bg-white rounded-lg shadow-sm p-4 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900">
                        <div>
                            <p class="font-medium">{{ $order->event->name }}</p>
                            <p class="text-sm text-gray-600">{{ $order->itemsSummary() }} · {{ $order->event->starts_at->translatedFormat('d M Y') }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-semibold">{{ Number::currency($order->total, 'IDR', 'id', 0) }}</p>
                            <p class="text-gray-600">
                                @if ($order->status === App\Enums\OrderStatus::Paid)
                                    {{ $order->tickets_count }} e-ticket
                                @else
                                    {{ $order->status->label() }}
                                @endif
                            </p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-public-layout>
