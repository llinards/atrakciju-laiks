<?php

use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot view the faq admin page', function () {
    $this->get(route('faqs.edit'))->assertRedirect(route('login'));
});

test('faq admin page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('faqs.edit'))->assertOk();
});

test('a question can be created', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.faqs')
        ->call('create')
        ->set('question', 'Vai piegāde ir bez maksas?')
        ->set('answer', 'Piegādes cena ir atkarīga no attāluma.')
        ->call('save')
        ->assertHasNoErrors();

    expect(Faq::query()->sole())
        ->question->toBe('Vai piegāde ir bez maksas?')
        ->is_visible->toBeTrue();
});

test('a question can be updated', function () {
    $this->actingAs(User::factory()->create());

    $faq = Faq::factory()->create();

    Livewire::test('pages::admin.faqs')
        ->call('edit', $faq->id)
        ->assertSet('question', $faq->question)
        ->set('question', 'Atjaunināts jautājums?')
        ->set('answer', 'Atjaunināta atbilde.')
        ->call('save')
        ->assertHasNoErrors();

    expect($faq->refresh())
        ->question->toBe('Atjaunināts jautājums?')
        ->answer->toBe('Atjaunināta atbilde.');

    expect(Faq::query()->count())->toBe(1);
});

test('a question can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $faq = Faq::factory()->create();

    Livewire::test('pages::admin.faqs')
        ->call('delete', $faq->id);

    expect(Faq::query()->count())->toBe(0);
});

test('question and answer are required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.faqs')
        ->set('question', '')
        ->set('answer', '')
        ->call('save')
        ->assertHasErrors(['question', 'answer']);
});

test('visibility can be toggled', function () {
    $this->actingAs(User::factory()->create());

    $faq = Faq::factory()->create();

    Livewire::test('pages::admin.faqs')
        ->call('toggleVisibility', $faq->id);

    expect($faq->refresh()->is_visible)->toBeFalse();
});

test('visible questions are shown on the home page', function () {
    Faq::factory()->create(['question' => 'Redzams jautājums?']);
    Faq::factory()->hidden()->create(['question' => 'Paslēpts jautājums?']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Redzams jautājums?')
        ->assertDontSee('Paslēpts jautājums?');
});

test('the faq section is hidden when no questions are visible', function () {
    Faq::factory()->hidden()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Biežāk uzdotie jautājumi');
});
