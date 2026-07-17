@extends('errors.layout')

@section('title', 'Notiek apkope')
@section('code', '503')
@section('heading', 'Notiek plānota apkope')
@section('message', 'Vietne uz brīdi nav pieejama, jo veicam uzlabojumus. Drīz būsim atpakaļ — atsvaidzini lapu pēc pāris minūtēm.')

@section('actions')
    <x-public.button variant="sun" onclick="window.location.reload()">Atsvaidzināt lapu</x-public.button>
@endsection
