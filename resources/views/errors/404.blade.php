@extends('errors.layout')

@section('title', 'Lapa nav atrasta')
@section('code', '404')
@section('heading', 'Lapa nav atrasta')
@section('message', 'Diemžēl meklētā lapa neeksistē vai ir pārvietota. Iespējams, saite ir novecojusi vai adresē ieviesusies kļūda.')

@section('actions')
    <x-public.button variant="sun" :href="route('home')">Uz sākumlapu</x-public.button>
    <x-public.button variant="outline" onclick="history.back()">Atpakaļ</x-public.button>
@endsection
