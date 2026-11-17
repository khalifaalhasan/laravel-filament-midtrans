{{-- One-shot status message (e.g. "Berhasil masuk"), announced to screen readers and dismissible. --}}
@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-transition.opacity role="status"
        {{ $attributes->class('flex items-start justify-between gap-3 rounded-md border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-900') }}>
        <span class="flex items-center gap-2"><x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-brand-600" aria-hidden="true" />{{ session('status') }}</span>
        <button type="button" x-on:click="show = false" class="text-brand-700 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600" aria-label="Tutup pesan">
            <x-heroicon-m-x-mark class="h-4 w-4" aria-hidden="true" />
        </button>
    </div>
@endif
