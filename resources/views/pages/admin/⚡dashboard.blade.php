<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Panelis')] class extends Component {
    /**
     * @return array{total: int, visible: int}
     */
    #[Computed]
    public function products(): array
    {
        return [
            'total' => Product::query()->count(),
            'visible' => Product::query()->visible()->count(),
        ];
    }

    /**
     * @return array{total: int, visible: int}
     */
    #[Computed]
    public function categories(): array
    {
        return [
            'total' => Category::query()->count(),
            'visible' => Category::query()->visible()->count(),
        ];
    }

    /**
     * @return array{total: int, visible: int}
     */
    #[Computed]
    public function faqs(): array
    {
        return [
            'total' => Faq::query()->count(),
            'visible' => Faq::query()->visible()->count(),
        ];
    }

    #[Computed]
    public function heroSlideCount(): int
    {
        return HeroSlide::query()->count();
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Overview of your site content.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <flux:card class="flex flex-col gap-4" data-test="dashboard-products-card">
            <div class="flex items-center gap-2">
                <flux:icon.cube class="size-5 text-zinc-400" />
                <flux:heading>{{ __('Products') }}</flux:heading>
            </div>

            <div>
                <flux:heading size="xl">{{ $this->products['total'] }}</flux:heading>
                <flux:text class="mt-1">{{ __(':count visible', ['count' => $this->products['visible']]) }}</flux:text>
            </div>

            <div class="mt-auto flex gap-2">
                <flux:button size="sm" variant="primary" icon="plus" :href="route('products.create')" wire:navigate data-test="dashboard-add-product-button">
                    {{ __('Add product') }}
                </flux:button>

                <flux:button size="sm" :href="route('products.index')" wire:navigate>
                    {{ __('Manage') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4" data-test="dashboard-categories-card">
            <div class="flex items-center gap-2">
                <flux:icon.squares-2x2 class="size-5 text-zinc-400" />
                <flux:heading>{{ __('Categories') }}</flux:heading>
            </div>

            <div>
                <flux:heading size="xl">{{ $this->categories['total'] }}</flux:heading>
                <flux:text class="mt-1">{{ __(':count visible', ['count' => $this->categories['visible']]) }}</flux:text>
            </div>

            <div class="mt-auto flex gap-2">
                <flux:button size="sm" :href="route('categories.edit')" wire:navigate>
                    {{ __('Manage') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4" data-test="dashboard-faqs-card">
            <div class="flex items-center gap-2">
                <flux:icon.question-mark-circle class="size-5 text-zinc-400" />
                <flux:heading>{{ __('FAQ') }}</flux:heading>
            </div>

            <div>
                <flux:heading size="xl">{{ $this->faqs['total'] }}</flux:heading>
                <flux:text class="mt-1">{{ __(':count visible', ['count' => $this->faqs['visible']]) }}</flux:text>
            </div>

            <div class="mt-auto flex gap-2">
                <flux:button size="sm" :href="route('faqs.edit')" wire:navigate>
                    {{ __('Manage') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4" data-test="dashboard-hero-slides-card">
            <div class="flex items-center gap-2">
                <flux:icon.photo class="size-5 text-zinc-400" />
                <flux:heading>{{ __('Hero images') }}</flux:heading>
            </div>

            <div>
                <flux:heading size="xl">{{ $this->heroSlideCount }} / {{ HeroSlide::MAX_SLIDES }}</flux:heading>
                <flux:text class="mt-1">{{ __('Slides on the home page.') }}</flux:text>
            </div>

            <div class="mt-auto flex gap-2">
                <flux:button size="sm" :href="route('hero-slides.edit')" wire:navigate>
                    {{ __('Manage') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4" data-test="dashboard-contact-card">
            <div class="flex items-center gap-2">
                <flux:icon.phone class="size-5 text-zinc-400" />
                <flux:heading>{{ __('Contact information') }}</flux:heading>
            </div>

            <div class="min-w-0">
                <flux:text class="truncate">{{ config('site.phone') }}</flux:text>
                <flux:text class="mt-1 truncate">{{ config('site.email') }}</flux:text>
            </div>

            <div class="mt-auto flex gap-2">
                <flux:button size="sm" :href="route('site-settings.edit')" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4" data-test="dashboard-public-site-card">
            <div class="flex items-center gap-2">
                <flux:icon.globe-alt class="size-5 text-zinc-400" />
                <flux:heading>{{ __('Public site') }}</flux:heading>
            </div>

            <flux:text>{{ __('See the site as your visitors do.') }}</flux:text>

            <div class="mt-auto flex gap-2">
                <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('home')" target="_blank">
                    {{ __('View public site') }}
                </flux:button>
            </div>
        </flux:card>
    </div>
</section>
