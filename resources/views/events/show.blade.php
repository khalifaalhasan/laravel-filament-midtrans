<x-public-layout :title="$event->name">
    <a href="{{ route('events.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Semua event</a>

    <article class="mt-4 bg-white rounded-lg shadow-sm overflow-hidden">
        @if ($event->poster_path)
            <img src="{{ Storage::disk('public')->url($event->poster_path) }}" alt="Poster {{ $event->name }}" class="w-full max-h-96 object-cover">
        @endif

        <div class="p-6">
            <h1 class="text-2xl font-semibold">{{ $event->name }}</h1>
            <dl class="mt-2 text-gray-700 space-y-1">
                <div><dt class="sr-only">Waktu</dt><dd>{{ $event->starts_at->translatedFormat('d M Y, H:i') }} – {{ $event->ends_at->translatedFormat('d M Y, H:i') }}</dd></div>
                <div><dt class="sr-only">Lokasi</dt><dd>{{ $event->location }}</dd></div>
            </dl>

            @if ($event->description)
                <p class="mt-4 whitespace-pre-line">{{ $event->description }}</p>
            @endif

            <h2 class="text-lg font-semibold mt-8 mb-3">Tiket</h2>
            @forelse ($event->ticketTypes as $ticket)
                <div class="flex items-center justify-between border-t border-gray-200 py-3">
                    <div>
                        <p class="font-medium">{{ $ticket->name }}</p>
                        <p class="text-sm text-gray-600">
                            @if ($ticket->remaining() === 0)
                                Habis
                            @elseif (! $ticket->isOnSale())
                                @if ($ticket->sale_starts_at?->isFuture())
                                    Dijual mulai {{ $ticket->sale_starts_at->translatedFormat('d M Y, H:i') }}
                                @else
                                    Penjualan ditutup
                                @endif
                            @else
                                Sisa {{ $ticket->remaining() }} tiket
                            @endif
                        </p>
                    </div>
                    <p class="font-semibold">{{ Number::currency($ticket->price, 'IDR', 'id', 0) }}</p>
                </div>
            @empty
                <p class="text-gray-600">Belum ada tiket.</p>
            @endforelse
        </div>
    </article>
</x-public-layout>
