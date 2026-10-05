@extends('layouts.pro')

@section('title', 'Tableau de bord')

@section('pro_content')
    @unless ($acteur)
        <div class="notice-panel mb-4">
            <i class="bi bi-building-exclamation" aria-hidden="true"></i>
            <div>
                <strong>Complétez votre profil société</strong> pour apparaître dans l'annuaire, recevoir des lots et saisir vos étapes.
                <a href="{{ route('pro.profil.edit') }}" class="ms-1">Compléter maintenant</a>
            </div>
        </div>
    @endunless

    <div class="kpi-grid mb-4">
        <a href="{{ route('pro.lots.index') }}" class="kpi-card">
            <span class="kpi-icon"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i></span>
            <span><span class="kpi-value">{{ $nbLotsChezMoi }}</span><span class="kpi-label">lot(s) en ma possession</span></span>
        </a>
        <a href="{{ route('pro.lots.index', ['vue' => 'historique']) }}" class="kpi-card">
            <span class="kpi-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
            <span><span class="kpi-value">{{ $nbLotsTraites }}</span><span class="kpi-label">lot(s) traité(s)</span></span>
        </a>
        <a href="{{ route('pro.lots.index') }}" class="kpi-card">
            <span @class(['kpi-icon', 'is-alert' => $nbAnalysesNonConformes > 0])><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i></span>
            <span><span class="kpi-value">{{ $nbAnalysesEnAttente }}</span><span class="kpi-label">analyse(s) en attente @if ($nbAnalysesNonConformes) · {{ $nbAnalysesNonConformes }} non conforme(s) @endif</span></span>
        </a>
        @if ($gereProduits)
            <a href="{{ route('pro.produits.index') }}" class="kpi-card">
                <span class="kpi-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                <span><span class="kpi-value">{{ $nbProduits }}</span><span class="kpi-label">produit(s)</span></span>
            </a>
            <a href="{{ route('pro.certifications.index') }}" class="kpi-card">
                <span class="kpi-icon"><i class="bi bi-award" aria-hidden="true"></i></span>
                <span><span class="kpi-value">{{ $nbCertificationsValides }}</span><span class="kpi-label">certification(s) valide(s)@if ($nbCertificationsEnAttente) · {{ $nbCertificationsEnAttente }} en attente @endif</span></span>
            </a>
            <a href="{{ route('pro.signalements.index') }}" class="kpi-card">
                <span @class(['kpi-icon', 'is-alert' => $nbSignalementsEnAttente > 0])><i class="bi bi-flag" aria-hidden="true"></i></span>
                <span><span class="kpi-value">{{ $nbSignalementsEnAttente }}</span><span class="kpi-label">signalement(s) en attente</span></span>
            </a>
        @endif
    </div>

    <div class="row g-4">
        <div @class(['col-lg-7' => $gereProduits, 'col-12' => ! $gereProduits])>
            <div class="panel h-100">
                <div class="pro-toolbar">
                    <h2 class="panel-title mb-0"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>Mes lots en cours</h2>
                    @can('pro-creer-lot')
                        <a href="{{ route('pro.lots.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouveau lot</a>
                    @endcan
                </div>
                @if ($lotsChezMoi->isEmpty())
                    <p class="text-muted mb-0">Aucun lot en votre possession pour le moment.</p>
                @else
                    <ul class="alert-list">
                        @foreach ($lotsChezMoi as $lot)
                            <li>
                                <span>
                                    <a href="{{ route('pro.lots.show', $lot) }}" class="fw-semibold">{{ $lot->numero_lot }}</a>
                                    <span class="small text-muted d-block">{{ $lot->produit?->nom }} · {{ $lot->etapes_count }} étape(s)</span>
                                </span>
                                <a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        @if ($gereProduits)
            <div class="col-lg-5">
                <div class="panel mb-4">
                    <h2 class="panel-title"><i class="bi bi-alarm" aria-hidden="true"></i>Certifications expirant sous 30 jours</h2>
                    @if ($expirations->isEmpty())
                        <p class="text-muted mb-0">Aucune certification n'expire prochainement.</p>
                    @else
                        <ul class="alert-list">
                            @foreach ($expirations as $certification)
                                <li>
                                    <span>
                                        <x-front.certification-badge :certification="$certification" />
                                        <span class="small text-muted d-block mt-1">{{ $certification->produit?->nom }} · n° {{ $certification->numero }}</span>
                                    </span>
                                    <span class="status-pill status-expired text-nowrap">{{ $certification->joursRestants() }} j</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="panel">
                    <h2 class="panel-title"><i class="bi bi-flag" aria-hidden="true"></i>Derniers signalements reçus</h2>
                    @if ($signalements->isEmpty())
                        <p class="text-muted mb-0">Aucun signalement sur vos produits.</p>
                    @else
                        <ul class="alert-list">
                            @foreach ($signalements as $signalement)
                                <li>
                                    <span>
                                        <strong>{{ $signalement->produit?->nom }}</strong>
                                        <span class="small text-muted d-block">{{ $signalement->motifLabel() }} · {{ $signalement->created_at?->format('d/m/Y') }}</span>
                                    </span>
                                    <span class="status-pill {{ $signalement->statutClasse() }}">{{ $signalement->statutLabel() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
