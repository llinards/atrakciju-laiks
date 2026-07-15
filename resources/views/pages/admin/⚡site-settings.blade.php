<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kontaktinformācija')] class extends Component {
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $facebook = '';
    public string $youtube = '';

    /**
     * Mount the component with the currently effective site settings.
     */
    public function mount(): void
    {
        $this->phone = config('site.phone');
        $this->email = config('site.email');
        $this->address = config('site.address');
        $this->facebook = config('site.facebook');
        $this->youtube = config('site.youtube');
    }

    /**
     * Persist the contact information used across the public site.
     */
    public function updateSiteSettings(): void
    {
        $validated = $this->validate([
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'facebook' => ['required', 'url', 'max:255'],
            'youtube' => ['required', 'url', 'max:255'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        Flux::toast(variant: 'success', text: __('Contact information updated.'));
    }
}; ?>

<section class="mx-auto w-full max-w-2xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Contact information') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Contact information shown across the public site') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <form wire:submit="updateSiteSettings" class="w-full space-y-6">
        <flux:input wire:model="phone" :label="__('Phone')" type="text" required />

        <flux:input wire:model="email" :label="__('Email')" type="email" required />

        <flux:input wire:model="address" :label="__('Address')" type="text" required />

        <flux:input wire:model="facebook" :label="__('Facebook URL')" type="url" required />

        <flux:input wire:model="youtube" :label="__('YouTube URL')" type="url" required />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" data-test="update-site-settings-button">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</section>
