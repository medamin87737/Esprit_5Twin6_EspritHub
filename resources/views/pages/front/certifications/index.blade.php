@extends('layouts.front')

@section('title', 'Vérifier un label')

@section('content')
    @php
        $certification = $certification ?? null;
        $certifications = $certifications ?? collect();
        $numero = trim((string) request('numero'));
        $types = config('nutritrace.options.certification_types');
        $statutClasses = ['valide' => 'status-valid', 'expiree' => 'status-expired', 'suspendue' => 'status-suspended'];
    @endphp

    <x-front.page-header eyebrow="Labels" title="Ce label est-il valide ?" image="consommateur-salade.webp"
                         subtitle="Vérifiez en quelques secondes qu'une certification bio, locale ou équitable est authentique et toujours en cours de validité." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <form class="filter-bar search-hero" method="GET" action="{{ route('front.certifications.index') }}" role="search">
                <label class="form-label" for="numero">Numéro de certification</label>
                <div class="search-hero-row">
                    <div class="field-icon flex-grow-1">
                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                        <input class="form-control form-control-lg" id="numero" type="search" name="numero" value="{{ $numero }}"
                               placeholder="Ex. BIO-TN-2026-001" autocomplete="off" required>
                    </div>
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>Vérifier</button>
                </div>
                @if (($exemples ?? collect())->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small text-muted">
                        <span>Exemples :</span>
                        @foreach ($exemples as $exemple)
                            <a href="{{ route('front.certifications.index', ['numero' => $exemple]) }}" class="label-chip text-decoration-none">{{ $exemple }}</a>
                        @endforeach
                    </div>
                @endif
            </form>

            @if ($certification)
                @php($estValide = $certification->estValide())
                <div class="verify-result {{ $estValide ? 'is-valid' : 'is-invalid' }}">
                    <span class="verify-result-icon"><i class="bi {{ $estValide ? 'bi-patch-check-fill' : 'bi-x-octagon-fill' }}" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="h5 mb-1">{{ $estValide ? 'Certification valide' : 'Certification non valide' }}</h2>
                        <p class="mb-2">
                            Label <strong>{{ $certification->typeLabel() }}</strong> n° {{ $certification->numero }}
                            pour <strong>{{ $certification->produit?->nom }}</strong>@if ($certification->produit?->categorie) ({{ $certification->produit->categorie->nom }})@endif,
                            délivré par <strong>{{ $certification->organisme?->nom }}</strong> ({{ $certification->organisme?->pays }}, accréditation {{ $certification->organisme?->accreditation }}).
                        </p>
                        <span class="status-pill {{ $statutClasses[$certification->statutEffectif()] ?? '' }}">{{ $certification->statutLabel() }}</span>
                        <span class="small text-muted ms-2">Valable du {{ $certification->date_obtention?->format('d/m/Y') }} au {{ $certification->date_expiration?->format('d/m/Y') }}</span>
                        @if ($certification->organisme?->site_web)
                            <a href="{{ $certification->organisme->site_web }}" class="small ms-2" target="_blank" rel="noopener">Site de l'organisme <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                        @endif
                    </div>
                </div>
            @elseif ($numero !== '')
                <x-front.empty-state icon="bi-question-circle" title="Aucune certification ne porte le numéro « {{ $numero }} »" class="mt-5">
                    Vérifiez la saisie. Un numéro introuvable peut signaler un label non reconnu par NutriTrace.
                </x-front.empty-state>
            @endif

            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-5 mb-3">
                <div>
                    <span class="eyebrow mb-1">Registre</span>
                    <h2 class="h4 mb-0">Labels enregistrés</h2>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('front.certifications.index') }}" class="label-chip text-decoration-none {{ request('type') ? '' : 'fw-semibold' }}">Tous</a>
                    @foreach ($types as $valeur => $libelle)
                        <a href="{{ route('front.certifications.index', ['type' => $valeur]) }}"
                           class="label-chip text-decoration-none {{ request('type') === $valeur ? 'fw-semibold' : '' }}">{{ $libelle }}</a>
                    @endforeach
                    <span class="text-muted small ms-2">{{ $certifications->total() }} certification{{ $certifications->total() > 1 ? 's' : '' }}</span>
                </div>
            </div>

            @if ($certifications->isEmpty())
                <x-front.empty-state icon="bi-award" title="Aucun label enregistré pour l'instant">
                    Les certifications délivrées par les organismes partenaires apparaîtront ici.
                </x-front.empty-state>
            @else
                <div class="data-card">
                    <div class="table-responsive">
                        <table class="table data-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Numéro</th>
                                    <th scope="col">Label</th>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Organisme</th>
                                    <th scope="col">Expiration</th>
                                    <th scope="col">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($certifications as $item)
                                    <tr>
                                        <td class="fw-semibold"><a href="{{ route('front.certifications.index', ['numero' => $item->numero]) }}" class="text-reset">{{ $item->numero }}</a></td>
                                        <td><span class="label-chip"><i class="bi {{ $item->icone() }} me-1" aria-hidden="true"></i>{{ $item->typeLabel() }}</span></td>
                                        <td>{{ $item->produit?->nom }}</td>
                                        <td class="text-muted">{{ $item->organisme?->nom }}</td>
                                        <td class="text-muted">{{ $item->date_expiration?->format('d/m/Y') }}</td>
                                        <td><span class="status-pill {{ $statutClasses[$item->statutEffectif()] ?? '' }}">{{ $item->statutLabel() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-5 d-flex justify-content-center">{{ $certifications->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </section>
@endsection
