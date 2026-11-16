<x-public-layout title="Event">
    <h1 class="text-2xl font-semibold mb-6">Event mendatang</h1>

    @if ($events->isEmpty())
        <p class="text-gray-600">Belum ada event yang dibuka.</p>
    @else
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <li class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <a href="{{ route('events.show', $event) }}" class="block hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900">
                        @if ($event->poster_path)
                            <img src="{{ Storage::disk('public')->url($event->poster_path) }}" alt="" class="w-full aspect-video object-cover">
                        @endif
                        <div class="p-4">
                            <h2 class="font-semibold">{{ $event->name }}</h2>
                            <p class="text-sm text-gray-600 mt-1">{{ $event->starts_at->translatedFormat('d M Y, H:i') }}</p>
                            <p class="text-sm text-gray-600">{{ $event->location }}</p>
                            @if ($event->min_price !== null)
                                <p class="text-sm font-medium mt-2">Mulai {{ Number::currency($event->min_price, 'IDR', 'id', 0) }}</p>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-8">{{ $events->links() }}</div>
    @endif
</x-public-layout>
