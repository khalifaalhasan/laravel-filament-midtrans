@props(['event'])
{{-- Maps an Event (with min_price from withStartingPrice) onto the reference card. --}}
@php($url = fn (?string $path) => $path ? Storage::disk('public')->url($path) : null)
<x-ui.event-card {{ $attributes }}
    :href="route('events.show', $event)"
    :title="$event->name"
    :date="$event->dateRangeLabel()"
    :image="$url($event->poster_path)"
    :price="$event->min_price !== null ? (int) $event->min_price : null"
    :price-label="$event->min_price === null ? 'Tiket habis' : null"
    :organizer="$event->organizer_name"
    :organizer-logo="$url($event->organizer_logo_path)" />
