@extends('errors.layout')

@section('title', 'Sesija ir beigusies')
@section('code', '419')
@section('heading', 'Sesija ir beigusies')
@section('message', 'Lapa bija atvērta pārāk ilgi, un drošības apsvērumu dēļ sesija tika slēgta. Atsvaidzini lapu un mēģini vēlreiz.')

@section('actions')
    <x-public.button variant="sun" onclick="window.location.reload()">Atsvaidzināt lapu</x-public.button>
@endsection
