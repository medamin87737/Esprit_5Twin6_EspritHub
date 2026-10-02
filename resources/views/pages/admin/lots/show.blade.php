@extends('layouts.admin')

@section('title', $lot->numero_lot)

@section('content')
    <x-admin.page-header :title="$lot->numero_lot" module="Module 3 · Lot"
                         :subtitle="$lot->produit?->nom" :back="route('admin.lots.index')">
        <x-slot:actions>
            <a href="{{ route('front.lots.search', ['numero' => $lot->numero_lot]) }}" class="btn btn-light" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Vue publique
            </a>
            <a href="{{ route('admin.lots.edit', $lot) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Informations du lot</h2></div>
                <div class="card-body">
                    <div class="nt-role-row">
                        <span class="text-muted">Produit</span>
                        @if ($lot->produit)
                            <a href="{{ route('admin.produits.show', $lot->produit) }}" class="nt-badge">{{ $lot->produit->nom }}</a>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Catégorie</span><strong>{{ $lot->produit?->categorie?->nom ?? '—' }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Quantité</span><strong>{{ number_format($lot->quantite, 0, ',', ' ') }} unités</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Production</span><strong>{{ $lot->date_production?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Péremption</span>
                        @if ($lot->estPerime())
                            <span class="nt-badge nt-badge-danger">{{ $lot->date_peremption->format('d/m/Y') }} · périmé</span>
                        @else
                            <strong>{{ $lot->date_peremption?->format('d/m/Y') }}</strong>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Étapes</span><strong>{{ $lot->etapes->count() }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$lot->etapes" title="Parcours du lot (ordre chronologique)">
                <x-slot:head>
                    <th scope="col">#</th>
                    <th scope="col">Type</th>
                    <th scope="col">Acteur</th>
                    <th scope="col">Lieu</th>
                    <th scope="col">Date et heure</th>
                    <th scope="col">Transport</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($lot->etapes as $etape)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td><span class="nt-badge">{{ $etape->typeLabel() }}</span></td>
                        <td>
                            @if ($etape->acteur)
                                <a href="{{ route('admin.acteurs.show', $etape->acteur) }}" class="nt-cell-title">{{ $etape->acteur->nom }}</a>
                                <div class="nt-cell-sub">{{ $etape->acteur->typeActeur?->libelle }}</div>
                            @endif
                        </td>
                        <td class="text-muted">{{ $etape->lieu }}</td>
                        <td class="text-muted">{{ $etape->date_heure?->format('d/m/Y H:i') }}</td>
                        <td class="text-muted">{{ $etape->transportLabel() }}</td>
                        <td class="text-right">
                            <x-admin.row-actions
                                :show="route('admin.etapes.show', $etape)"
                                :edit="route('admin.etapes.edit', $etape)"
                                :delete="route('admin.etapes.destroy', $etape)"
                                confirm="Supprimer cette étape du parcours ?" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-signpost-split" title="Aucune étape pour ce lot">
                        Ajoutez la production, la transformation, la distribution puis la vente pour reconstituer le parcours.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <a href="{{ route('admin.etapes.create', ['lot' => $lot->id]) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter une étape à ce lot
            </a>
        </div>
    </div>
@endsection
