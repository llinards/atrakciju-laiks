@php
    $phoneHref = 'tel:'.str_replace(' ', '', config('site.phone'));
@endphp

<div {{ $attributes->merge(['class' => 'fixed right-3 top-1/2 z-30 -translate-y-1/2']) }}>
    <div class="flex flex-col items-center gap-5 rounded-full bg-brand px-3 py-6 shadow-lg">
        <a href="{{ $phoneHref }}" class="text-white transition-opacity hover:opacity-80" aria-label="Zvanīt {{ config('site.phone') }}">
            <x-public.icons.phone class="size-6" />
        </a>
        <a href="mailto:{{ config('site.email') }}" class="text-white transition-opacity hover:opacity-80" aria-label="Rakstīt e-pastu">
            <x-public.icons.mail class="size-6" />
        </a>
        <a href="{{ config('site.facebook') }}" target="_blank" rel="noopener" class="text-white transition-opacity hover:opacity-80" aria-label="Facebook">
            <x-public.icons.facebook class="size-6" />
        </a>
    </div>
</div>
