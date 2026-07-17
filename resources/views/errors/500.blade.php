@extends('errors.layout')

@section('title', 'Radās kļūda')
@section('code', '500')
@section('heading', 'Kaut kas nogāja greizi')
@section('message')
    Mūsu pusē radās negaidīta kļūda — mēs pie tās jau strādājam.
    Ja nepieciešama palīdzība, zvani
    <a href="tel:{{ str_replace(' ', '', config('site.phone')) }}" class="font-semibold text-brand underline underline-offset-2">{{ config('site.phone') }}</a>
    vai raksti uz
    <a href="mailto:{{ config('site.email') }}" class="font-semibold text-brand underline underline-offset-2">{{ config('site.email') }}</a>.
@endsection

@section('actions')
    <x-public.button variant="sun" :href="route('home')">Uz sākumlapu</x-public.button>
@endsection
