@extends('layouts.app')

@section('title', __('app.offline_title').' — Festes Majors de Catalunya')

@section('content')
    <section id="avis" aria-labelledby="avis-titol">
        <x-icon name="logo" size="64" />
        <h1 id="avis-titol">{{ __('app.offline_title') }}</h1>
        <p>{{ __('app.offline_text') }}</p>
        <a href="{{ route('home') }}">{{ __('app.retry') }}</a>
    </section>
@endsection
