@extends('layouts.app')

@section('title', __('app.not_found_title').' — Festes Majors de Catalunya')

@section('content')
    <section id="avis" aria-labelledby="avis-titol">
        <x-icon name="logo" size="64" />
        <h1 id="avis-titol">{{ __('app.not_found_title') }}</h1>
        <p>{{ __('app.not_found_text') }}</p>
        <a href="{{ route('home') }}">{{ __('app.back_home') }}</a>
    </section>
@endsection
