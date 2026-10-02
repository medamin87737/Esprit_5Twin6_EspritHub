@extends('layouts.admin')

@section('title', 'Nouvelle empreinte')

@section('content')
    <x-admin.page-header title="Nouvelle empreinte carbone" module="Module 4 · Empreinte environnementale"
                         subtitle="Enregistrez le bilan CO₂ d'un lot."
                         :back="route('admin.empreintes.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Bilan carbone du lot"
                               :action="Route::has('admin.empreintes.store') ? route('admin.empreintes.store') : null"
                               :cancel="route('admin.empreintes.index')">
                @include('pages.admin.empreintes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card title="Barème de l'éco-score" icon="bi-bar-chart-steps" parent="Empreinte" child="Indicateurs">
                <ul class="nt-rule-list mt-n2">
                    @foreach (config('nutritrace.options.scores') as $score => $seuil)
                        <li class="align-items-center">
                            <span class="nt-score nt-score-{{ strtolower($score) }}">{{ $score }}</span>
                            <span>{{ $seuil }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-admin.help-card>
            <x-admin.help-card :rules="[
                'Lot' => 'doit exister.',
                'CO₂ total' => 'nombre positif, en kg.',
                'Date du calcul' => 'obligatoire.',
                'Méthode' => 'obligatoire (ACV, Agribalyse…).',
            ]" />
        </div>
    </div>
@endsection
