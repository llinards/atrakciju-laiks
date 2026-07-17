@extends('errors.layout')

@section('title', 'Pārāk daudz pieprasījumu')
@section('code', '429')
@section('heading', 'Pārāk daudz pieprasījumu')
@section('message', 'Īsā laikā saņemts pārāk daudz pieprasījumu. Uzgaidi brīdi un mēģini vēlreiz.')

@section('actions')
    <x-public.button variant="outline" onclick="history.back()">Atpakaļ</x-public.button>
@endsection
