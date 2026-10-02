@extends('layouts.front')

@section('title', 'Accueil')

@section('content')
    @include('partials.front.home.hero')
    @include('partials.front.home.galerie')
    @include('partials.front.home.mission')
    @include('partials.front.home.parcours')
    @include('partials.front.home.ecoscore')
    @include('partials.front.home.certifications')
    @include('partials.front.home.cta')
    @include('partials.front.home.contact')
@endsection
