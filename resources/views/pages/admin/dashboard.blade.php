@extends('layouts.admin')

@section('title', "Vue d'ensemble")

@section('content')
    @include('pages.admin.dashboard._bienvenue')

    @include('pages.admin.dashboard._entete')

    @include('pages.admin.dashboard._kpis')

    <div class="row">
        <div class="col-lg-7 col-xl-8 mb-4 d-flex">
            @include('pages.admin.dashboard._tendance')
        </div>
        <div class="col-lg-5 col-xl-4 mb-4 d-flex flex-column nt-dash-stack">
            @include('pages.admin.dashboard._repartition')
            @include('pages.admin.dashboard._indice')
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4 d-flex">
            @include('pages.admin.dashboard._certifications')
        </div>
        <div class="col-lg-6 mb-4 d-flex">
            @include('pages.admin.dashboard._environnement')
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-xl-8 mb-4 d-flex">
            @include('pages.admin.dashboard._activite')
        </div>
        <div class="col-lg-5 col-xl-4 mb-4 d-flex">
            @include('pages.admin.dashboard._alertes')
        </div>
    </div>

    <script type="application/json" id="nt-dashboard-data">@json($graphiques)</script>
@endsection

@push('scripts')
    @vite('resources/assets/admin/js/dashboard.js')
@endpush
