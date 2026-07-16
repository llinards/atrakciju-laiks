@php
    // Visible categories drive the menu, followed by the static pages.
    $menuItems = [
        ...\App\Models\Category::navigation()
            ->map(fn (\App\Models\Category $category): array => [
                'label' => $category->title,
                'href' => route('category.show', $category->slug),
            ])
            ->all(),
        ['label' => 'Galerija', 'href' => '#'],
        ['label' => 'Pārdošanas sadaļa', 'href' => route('sale.index')],
        ['label' => 'Kontakti', 'href' => route('contact')],
    ];
@endphp

<footer {{ $attributes->merge(['class' => 'border-t border-gray-200']) }}>
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 md:grid-cols-2 lg:grid-cols-[1fr_auto_auto] lg:gap-24 lg:px-8">
        <div class="flex max-w-sm flex-col items-start gap-6">
            <a href="{{ route('home') }}" wire:navigate>
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-24 w-auto">
            </a>

            <p class="font-heading text-xl font-semibold leading-7 text-gray-700">
                Izvēlies. Rezervē. Atpūties!<br>
                Atrakciju piegādāsim un uzstādīsim Jums ērtā vietā visā Latvijā.
            </p>

            <div class="flex flex-col gap-2">
                <p class="font-heading text-lg font-bold text-gray-900">Seko līdzi jaunumiem</p>
                <div class="flex items-center gap-4">
                    <a href="{{ config('site.facebook') }}" target="_blank" rel="noopener" class="text-gray-900 transition-opacity hover:opacity-70" aria-label="Facebook">
                        <x-public.icons.facebook class="size-6" />
                    </a>
                    <a href="{{ config('site.youtube') }}" target="_blank" rel="noopener" class="text-gray-900 transition-opacity hover:opacity-70" aria-label="YouTube">
                        <x-public.icons.youtube class="size-6" />
                    </a>
                </div>
            </div>
        </div>

        <nav class="flex flex-col gap-1" aria-label="Kājenes izvēlne">
            <p class="mb-2 font-heading text-lg font-bold text-gray-900">Izvēlne</p>
            @foreach ($menuItems as $item)
                <a href="{{ $item['href'] }}" @if ($item['href'] !== '#') wire:navigate @endif class="text-[17px] leading-[30px] text-gray-900/50 transition-colors hover:text-gray-900">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex max-w-60 flex-col gap-1">
            <p class="mb-2 font-heading text-lg font-bold text-gray-900">Kontakti</p>
            <a href="tel:{{ str_replace(' ', '', config('site.phone')) }}" class="text-[17px] leading-[30px] text-gray-900/50 transition-colors hover:text-gray-900">
                {{ config('site.phone') }}
            </a>
            <a href="mailto:{{ config('site.email') }}" class="text-[17px] leading-[30px] text-gray-900/50 transition-colors hover:text-gray-900">
                {{ config('site.email') }}
            </a>
            <p class="text-[17px] leading-[21px] text-gray-900/50">
                {{ config('site.address') }}
            </p>
        </div>
    </div>

    <div class="flex flex-col items-center gap-2 px-4 pb-8">
        <p class="text-center text-gray-900/50">
            Visas tiesības rezervētas. ©{{ date('Y') }} atrakcijulaiks.lv
        </p>
        <p class="text-center text-sm text-gray-900/50">
            Dizains:
            <a href="https://simpledesign.lv" target="_blank" rel="noopener" class="underline underline-offset-2 transition-colors hover:text-gray-900">SIMPLE DESIGN</a>
            | Izstrādāja:
            <a href="https://slmedia.lv" target="_blank" rel="noopener" class="underline underline-offset-2 transition-colors hover:text-gray-900">S&amp;L MEDIA</a>
        </p>
    </div>
</footer>
