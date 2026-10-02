@extends('layouts.front')

@section('title', 'Annuaire des acteurs')

@section('content')
    @php
        $acteurs = $acteurs ?? collect();
        $typeActeurs = $typeActeurs ?? collect();
        $pays = $pays ?? collect();
        $filtered = request()->filled('q') || request()->filled('type') || request()->filled('pays');
        $total = $acteurs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $acteurs->total() : $acteurs->count();
    @endphp

    <x-front.page-header eyebrow="Annuaire" title="Les acteurs de la chaîne" image="recolte-ble.webp"
                         subtitle="Producteurs, transformateurs et distributeurs qui font vivre les produits tracés par NutriTrace." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <form class="filter-bar" method="GET" action="{{ route('front.acteurs.index') }}" role="search">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="q">Rechercher un acteur</label>
                        <div class="field-icon">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input class="form-control" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom de l'exploitation, de l'usine…">
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label" for="type">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Tous les types</option>
                            @foreach ($typeActeurs as $typeActeur)
                                <option value="{{ $typeActeur->id }}" @selected((string) request('type') === (string) $typeActeur->id)>{{ $typeActeur->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label" for="pays">Pays</label>
                        <select class="form-select" id="pays" name="pays">
                            <option value="">Tous</option>
                            @foreach ($pays as $p)
                                <option value="{{ $p }}" @selected(request('pays') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Filtrer</button>
                    </div>
                </div>
            </form>

            <div class="results-bar">
                <span><strong>{{ $total }}</strong> acteur{{ $total > 1 ? 's' : '' }}</span>
                @if ($filtered)
                    <a href="{{ route('front.acteurs.index') }}" class="small"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Effacer les filtres</a>
                @endif
            </div>

            @if ($total === 0)
                @if ($filtered)
                    <x-front.empty-state icon="bi-search" title="Aucun acteur ne correspond à ces critères" :reset="route('front.acteurs.index')">
                        Modifiez le type ou le pays sélectionné.
                    </x-front.empty-state>
                @else
                    <x-front.empty-state icon="bi-people" title="L'annuaire est encore vide">
                        Les acteurs de la chaîne apparaîtront ici dès leur enregistrement sur la plateforme.
                    </x-front.empty-state>
                @endif
            @else
                <div class="row g-4">
                    @foreach ($acteurs as $acteur)
                        <div class="col-md-6 col-xl-4">
                            <article class="actor-card h-100">
                                <div class="actor-card-head">
                                    <span class="actor-avatar">{{ mb_strtoupper(mb_substr($acteur->nom, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <h2 class="actor-card-title">{{ $acteur->nom }}</h2>
                                        <span class="label-chip">{{ $acteur->typeActeur?->libelle }}</span>
                                    </div>
                                </div>
                                <ul class="actor-card-info">
                                    <li><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $acteur->adresse }}, {{ $acteur->pays }}</li>
                                    <li><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:{{ preg_replace('/\s+/', '', $acteur->telephone) }}">{{ $acteur->telephone }}</a></li>
                                    <li><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:{{ $acteur->email }}">{{ $acteur->email }}</a></li>
                                </ul>
                            </article>
                        </div>
                    @endforeach
                </div>

                @if ($acteurs instanceof \Illuminate\Contracts\Pagination\Paginator && $acteurs->hasPages())
                    <div class="mt-5 d-flex justify-content-center">{{ $acteurs->withQueryString()->links('pagination::bootstrap-5') }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
