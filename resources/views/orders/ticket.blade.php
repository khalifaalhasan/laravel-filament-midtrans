{{-- E-ticket detail, print-ready. No dedicated reference: card style from the checkout pages. --}}
@php($event = $ticket->event)
@php($valid = $ticket->isValid())
<x-public-layout :title="'E-ticket '.$ticket->code">
    <div class="mx-auto max-w-md">
        <a href="{{ route('orders.show', $ticket->order) }}" class="text-sm text-gray-600 hover:text-ink print:hidden">&larr; Kembali ke pesanan</a>

        <article class="mt-3 overflow-hidden rounded-card bg-brand-50 p-2 print:bg-white print:p-0" aria-labelledby="ticket-title">
            @if ($event->poster_path)
                <img src="{{ Storage::disk('public')->url($event->poster_path) }}" alt="" class="aspect-[5/2] w-full rounded-lg object-cover">
            @endif

            <div class="mt-2 rounded-lg bg-white p-4">
                <p class="text-xs font-medium text-brand-700">{{ $ticket->ticketType?->name }}</p>
                <h1 id="ticket-title" class="mt-1 text-lg font-semibold">{{ $event->name }}</h1>
                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex items-center gap-2"><dt><x-heroicon-s-calendar-days class="h-4 w-4 text-brand-400" /><span class="sr-only">Tanggal</span></dt><dd>{{ $event->dateRangeLabel() }}</dd></div>
                    <div class="flex items-center gap-2"><dt><x-heroicon-s-clock class="h-4 w-4 text-brand-400" /><span class="sr-only">Waktu</span></dt><dd>{{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}</dd></div>
                    <div class="flex items-center gap-2"><dt><x-heroicon-s-map-pin class="h-4 w-4 text-brand-400" /><span class="sr-only">Lokasi</span></dt><dd>{{ $event->location }}</dd></div>
                </dl>
            </div>

            {{-- Ticket-stub divider with side notches --}}
            <div class="relative h-px bg-white" aria-hidden="true">
                <div class="mx-4 border-t border-dashed border-gray-200"></div>
                <span class="absolute -left-2 -top-2 h-4 w-4 rounded-full bg-brand-50 print:hidden"></span>
                <span class="absolute -right-2 -top-2 h-4 w-4 rounded-full bg-brand-50 print:hidden"></span>
            </div>

            <div class="rounded-lg bg-white p-4 text-center">
                <div @class(['mx-auto w-52', 'opacity-25' => ! $valid]) role="img" aria-label="QR e-ticket {{ $ticket->code }}">{!! $ticket->qrSvg(208) !!}</div>
                <p class="mt-2 font-mono text-base font-semibold tracking-wider">{{ $ticket->code }}</p>

                <p class="mt-3">
                    @if (! $valid)
                        <x-ui.badge variant="danger">Tidak berlaku: pesanan {{ strtolower($ticket->order->status->label()) }}</x-ui.badge>
                    @elseif ($ticket->checked_in_at)
                        <x-ui.badge variant="soft">Sudah check-in {{ $ticket->checked_in_at->translatedFormat('d M Y, H:i') }}</x-ui.badge>
                    @else
                        <x-ui.badge>Belum check-in</x-ui.badge>
                    @endif
                </p>

                <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-dashed border-gray-200 pt-4 text-left text-sm">
                    <div><dt class="text-xs text-gray-600">Pemegang</dt><dd class="font-medium">{{ $ticket->holder_name ?? $ticket->order->buyer_name ?? $ticket->order->user->name }}</dd></div>
                    <div><dt class="text-xs text-gray-600">Kode pesanan</dt><dd class="break-all font-mono text-xs">{{ $ticket->order->code }}</dd></div>
                </dl>
                <p class="mt-4 text-xs text-gray-600">Tunjukkan QR ini dan kartu identitas asli saat check-in. Satu e-ticket untuk satu orang.</p>
            </div>
        </article>

        @if ($valid)
            <x-ui.button class="mt-4 w-full print:hidden" onclick="window.print()">
                <x-heroicon-o-printer class="h-4 w-4" aria-hidden="true" /> Cetak / Simpan PDF
            </x-ui.button>
        @endif
    </div>
</x-public-layout>
