<?php

use App\Models\Faq;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('BUJ')] class extends Component {
    public ?int $editingId = null;

    public string $question = '';

    public string $answer = '';

    /**
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()->ordered()->get();
    }

    /**
     * Open the form modal for a new question.
     */
    public function create(): void
    {
        $this->reset('editingId', 'question', 'answer');
        $this->resetValidation();

        Flux::modal('faq-form')->show();
    }

    /**
     * Open the form modal for an existing question.
     */
    public function edit(Faq $faq): void
    {
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->resetValidation();

        Flux::modal('faq-form')->show();
    }

    /**
     * Create or update the question being edited.
     */
    public function save(): void
    {
        $validated = $this->validate(
            rules: [
                'question' => ['required', 'string', 'max:255'],
                'answer' => ['required', 'string', 'max:2000'],
            ],
            attributes: [
                'question' => __('question attribute'),
                'answer' => __('answer attribute'),
            ],
        );

        Faq::query()->updateOrCreate(
            ['id' => $this->editingId],
            [...$validated, 'position' => $this->editingId !== null
                ? Faq::query()->whereKey($this->editingId)->value('position')
                : (int) Faq::query()->max('position') + 1],
        );

        $this->reset('editingId', 'question', 'answer');
        unset($this->faqs);

        Flux::modal('faq-form')->close();
        Flux::toast(variant: 'success', text: __('Question saved.'));
    }

    /**
     * Toggle whether the question is shown on the public site.
     */
    public function toggleVisibility(Faq $faq): void
    {
        $faq->update(['is_visible' => ! $faq->is_visible]);
        unset($this->faqs);
    }

    public function delete(Faq $faq): void
    {
        $faq->delete();
        unset($this->faqs);

        Flux::toast(variant: 'success', text: __('Question deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('FAQ') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Frequently asked questions on the home page. Hidden questions are not shown; the section disappears when no questions are visible.') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6 flex justify-end">
        <flux:button variant="primary" icon="plus" wire:click="create" data-test="add-faq-button">
            {{ __('Add question') }}
        </flux:button>
    </div>

    @if ($this->faqs->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Question') }}</flux:table.column>
                <flux:table.column>{{ __('Visible') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->faqs as $faq)
                    <flux:table.row :key="$faq->id">
                        <flux:table.cell class="max-w-md">
                            <p class="truncate font-medium">{{ $faq->question }}</p>
                            <p class="truncate text-sm text-zinc-500">{{ $faq->answer }}</p>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                :checked="$faq->is_visible"
                                wire:click="toggleVisibility({{ $faq->id }})"
                                aria-label="{{ __('Visible') }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $faq->id }})" aria-label="{{ __('Edit') }}" />
                            <flux:button
                                size="sm"
                                variant="danger"
                                icon="trash"
                                wire:click="delete({{ $faq->id }})"
                                wire:confirm="{{ __('Delete this question?') }}"
                                aria-label="{{ __('Delete this question?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>{{ __('No questions yet - the FAQ section is hidden on the home page.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:modal name="faq-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit question') : __('Add question') }}
            </flux:heading>

            <flux:input wire:model="question" :label="__('Question')" type="text" required />

            <flux:textarea wire:model="answer" :label="__('Answer')" rows="5" required />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-faq-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
