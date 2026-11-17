<x-filament-panels::page>
    <form wire:submit="checkIn" class="max-w-md space-y-4">
        {{ $this->form }}

        <x-filament::button type="submit">Check-in</x-filament::button>
    </form>
</x-filament-panels::page>
