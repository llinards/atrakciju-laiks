@props([
    'rows' => [],
])

<dl {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    @foreach ($rows as $label => $value)
        <div class="flex items-baseline justify-between gap-4 border-b border-gray-200 py-3">
            <dt class="text-gray-600">{{ $label }}</dt>
            <dd class="shrink-0 text-right font-semibold text-gray-900">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
