@props([
    'items' => [],
])

<ul {{ $attributes->merge(['class' => 'flex flex-col gap-2.5']) }}>
    @foreach ($items as $item)
        <li class="flex items-start gap-3">
            <x-public.icons.check class="size-6 shrink-0" />
            <span class="leading-6 text-gray-800">{{ $item }}</span>
        </li>
    @endforeach
</ul>
