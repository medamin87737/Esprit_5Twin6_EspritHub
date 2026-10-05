@extends('layouts.front')

@section('title', 'Mes signalements')

@section('content')
    <x-front.page-header eyebrow="Mon espace" title="Mes signalements" image="consommateur-salade.webp"
                         subtitle="Suivez vos signalements. Vous pouvez les modifier ou les supprimer tant qu'ils sont en attente d'examen." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            @if ($signalements->isEmpty())
                <x-front.empty-state icon="bi-flag" title="Vous n'avez fait aucun signalement">
                    Depuis la fiche d'un produit, utilisez le bouton « Signaler » si une information vous semble fausse ou trompeuse.
                </x-front.empty-state>
                <div class="text-center mt-3">
                    <a href="{{ route('front.produits.index') }}" class="btn btn-primary rounded-pill px-4">Parcourir le catalogue</a>
                </div>
            @else
                <div class="data-card">
                    <div class="table-responsive">
                        <table class="table data-table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Motif</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($signalements as $signalement)
                                    <tr>
                                        <td>
                                            @if ($signalement->produit)
                                                <a href="{{ route('front.produits.show', $signalement->produit) }}" class="cell-title">{{ $signalement->produit->nom }}</a>
                                            @endif
                                            <div class="cell-sub text-truncate" style="max-width: 22rem;">{{ $signalement->description }}</div>
                                        </td>
                                        <td>{{ $signalement->motifLabel() }}</td>
                                        <td>{{ $signalement->created_at?->format('d/m/Y') }}</td>
                                        <td><span class="status-pill {{ $signalement->statutClasse() }}">{{ $signalement->statutLabel() }}</span></td>
                                        <td class="cell-actions">
                                            @if ($signalement->preuve)
                                                <a href="{{ asset('storage/' . $signalement->preuve) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="Voir la preuve"><i class="bi bi-paperclip" aria-hidden="true"></i><span class="visually-hidden">Preuve</span></a>
                                            @endif
                                            @can('update', $signalement)
                                                <a href="{{ route('consommateur.signalements.edit', $signalement) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Modifier</span></a>
                                            @endcan
                                            @can('delete', $signalement)
                                                <form method="POST" action="{{ route('consommateur.signalements.destroy', $signalement) }}" class="d-inline"
                                                      onsubmit="return confirm('Supprimer ce signalement ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer</span></button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-front.pagination :items="$signalements" />
            @endif
        </div>
    </section>
@endsection
