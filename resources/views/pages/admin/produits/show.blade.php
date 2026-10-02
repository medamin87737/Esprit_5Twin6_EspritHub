@extends('layouts.admin')

@section('title', $produit->nom)

@section('content')
    @php($estAdmin = $espace === 'admin')

    <x-admin.page-header :title="$produit->nom" :module="$moduleLabel"
                         :subtitle="$produit->description" :back="route($espace . '.produits.index')">
        <x-slot:actions>
            <a href="{{ route($espace . '.produits.edit', $produit) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($produit->image)
                        <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}" class="img-fluid rounded">
                    @else
                        <span class="nt-thumb nt-thumb-placeholder mx-auto" style="width: 6rem; height: 6rem; font-size: 2rem;">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                        </span>
                        <p class="text-muted small mt-3 mb-0">Aucune image</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Fiche produit</h2></div>
                <div class="card-body">
                    <div class="nt-role-row">
                        <span class="text-muted">Catégorie</span>
                        @if ($produit->categorie && $estAdmin)
                            <a href="{{ route('admin.categories.show', $produit->categorie) }}" class="nt-badge">{{ $produit->categorie->nom }}</a>
                        @elseif ($produit->categorie)
                            <span class="nt-badge">{{ $produit->categorie->nom }}</span>
                        @endif
                    </div>
                    <div class="nt-role-row">
                        <span class="text-muted">Fournisseur</span>
                        @if ($produit->fournisseur)
                            <span><strong>{{ $produit->fournisseur->name }}</strong> <span class="text-muted small">· {{ $produit->fournisseur->email }}</span></span>
                        @else
                            <span class="text-muted">Administration</span>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Code-barres</span><strong>{{ $produit->code_barres }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Origine</span><strong>{{ $produit->origine }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Éco-score moyen</span>
                        @if ($produit->empreintes_avg_co2_total !== null)
                            @php($scoreMoyen = \App\Models\EmpreinteCarbone::scorePour((float) $produit->empreintes_avg_co2_total))
                            <span><span class="nt-score nt-score-{{ strtolower($scoreMoyen) }}">{{ $scoreMoyen }}</span> {{ number_format($produit->empreintes_avg_co2_total, 2, ',', ' ') }} kg CO₂e</span>
                        @else
                            <span class="text-muted">Non calculé</span>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Ajouté le</span><strong>{{ $produit->created_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Composition</h2></div>
                <div class="card-body">
                    <p class="mb-0 {{ $produit->composition ? '' : 'text-muted' }}">{{ $produit->composition ?: 'Non renseignée.' }}</p>
                </div>
            </div>

            <x-admin.table-card :items="$produit->certifications" title="Labels et certifications de ce produit">
                <x-slot:head>
                    <th scope="col">Numéro</th>
                    <th scope="col">Label</th>
                    <th scope="col">Organisme</th>
                    <th scope="col">Expiration</th>
                    <th scope="col">Statut</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($produit->certifications as $certification)
                    <tr>
                        <td class="nt-cell-title">{{ $certification->numero }}</td>
                        <td><span class="nt-badge"><i class="bi {{ $certification->icone() }}" aria-hidden="true"></i> {{ $certification->typeLabel() }}</span></td>
                        <td class="text-muted">{{ $certification->organisme?->nom }}</td>
                        <td class="text-muted">{{ $certification->date_expiration?->format('d/m/Y') }}</td>
                        <td>@include('pages.admin.certifications._statut')</td>
                        <td class="text-right"><x-admin.row-actions :show="$estAdmin ? route('admin.certifications.show', $certification) : null" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-award" title="Aucun label pour ce produit">
                        Les certifications bio, locales ou équitables (Module 5) délivrées à ce produit apparaîtront ici.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            @if ($estAdmin)
                <a href="{{ route('admin.certifications.create', ['produit' => $produit->id]) }}" class="btn btn-primary mb-4">
                    <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Attribuer un label à ce produit
                </a>
            @endif

            <x-admin.table-card :items="$produit->lots" :title="'Lots de ce produit · ' . $produit->etapes_count . ' étape(s) tracée(s)'">
                <x-slot:head>
                    <th scope="col">Numéro de lot</th>
                    <th scope="col" class="text-right">Quantité</th>
                    <th scope="col">Production</th>
                    <th scope="col">Péremption</th>
                    <th scope="col" class="text-center">Étapes</th>
                    <th scope="col" class="text-center">Score</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($produit->lots as $lot)
                    <tr>
                        <td class="nt-cell-title">{{ $lot->numero_lot }}</td>
                        <td class="text-right">{{ number_format($lot->quantite, 0, ',', ' ') }}</td>
                        <td class="text-muted">{{ $lot->date_production?->format('d/m/Y') }}</td>
                        <td class="text-muted">{{ $lot->date_peremption?->format('d/m/Y') }}</td>
                        <td class="text-center font-weight-600">{{ $lot->etapes_count }}</td>
                        <td class="text-center">
                            @if ($lot->empreinteCarbone)
                                <span class="nt-score nt-score-{{ strtolower($lot->empreinteCarbone->score) }}">{{ $lot->empreinteCarbone->score }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right"><x-admin.row-actions :show="$estAdmin ? route('admin.lots.show', $lot) : null" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-upc-scan" title="Aucun lot pour ce produit">
                        Les lots de production de ce produit (Module 3) apparaîtront ici.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <x-admin.table-card :items="$produit->acteurs" title="Acteurs qui prennent en charge ce produit">
                <x-slot:head>
                    <th scope="col">Acteur</th>
                    <th scope="col">Type</th>
                    <th scope="col">Pays</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($produit->acteurs as $acteur)
                    <tr>
                        <td class="nt-cell-title">{{ $acteur->nom }}</td>
                        <td><span class="nt-badge">{{ $acteur->typeActeur?->libelle }}</span></td>
                        <td>{{ $acteur->pays }}</td>
                        <td class="text-right"><x-admin.row-actions :show="$estAdmin ? route('admin.acteurs.show', $acteur) : null" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-people" title="Aucun acteur associé">
                        Associez ce produit à ses producteurs, transformateurs ou distributeurs depuis la fiche d'un acteur (Module 2).
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>
@endsection
