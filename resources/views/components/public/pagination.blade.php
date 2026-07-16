@props([
    'paginator',
    'prevLabel' => 'Iepriekšējie',
    'nextLabel' => 'Nākamie',
])

@if ($paginator->lastPage() > 1)
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4']) }} aria-label="Lapošana">
        <x-public.button variant="brand" wire:click="previousPage" :disabled="$paginator->onFirstPage()">
            <x-public.icons.arrow-left class="size-5" />
            {{ $prevLabel }}
        </x-public.button>

        <span class="text-sm font-semibold text-gray-600" aria-live="polite">
            {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }}
        </span>

        <x-public.button variant="brand" wire:click="nextPage" :disabled="! $paginator->hasMorePages()">
            {{ $nextLabel }}
            <x-public.icons.arrow-right class="size-5" />
        </x-public.button>
    </nav>
@endif
