@extends('layouts.pro')

@section('title', 'Signalements sur mes produits')

@section('pro_content')
    <div class="pro-toolbar">
        <form method="GET" action="{{ route('pro.signalements.index') }}" class="d-flex gap-2">
            <label for="statut" class="visually-hidden">Statut</label>
            <select class="form-select" id="statut" name="statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach (config('nutritrace.options.signalement_statuts') as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-outline-primary" type="submit">Filtrer</button></noscript>
        </form>
        <span class="small text-muted"><i class="bi bi-eye me-1" aria-hidden="true"></i>Lecture seule : les signalements sont examinés par l'équipe NutriTrace.</span>
    </div>

    @if ($signalements->isEmpty())
        <x-front.empty-state icon="bi-flag" title="Aucun signalement" :reset="request()->filled('statut') ? route('pro.signalements.index') : null">
            Aucun consommateur n'a signalé de problème sur vos produits.
        </x-front.empty-state>
    @else
        <div class="data-card">
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Produit</th>
                            <th scope="col">Motif</th>
                            <th scope="col">Description</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($signalements as $signalement)
                            <tr>
                                <td class="cell-title">{{ $signalement->produit?->nom }}</td>
                                <td>{{ $signalement->motifLabel() }}</td>
                                <td style="min-width: 16rem;">
                                    {{ $signalement->description }}
                                    @if ($signalement->preuve)
                                        <a href="{{ asset('storage/' . $signalement->preuve) }}" target="_blank" rel="noopener" class="d-block small mt-1"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>Voir la preuve</a>
                                    @endif
                                </td>
                                <td>{{ $signalement->created_at?->format('d/m/Y') }}</td>
                                <td><span class="status-pill {{ $signalement->statutClasse() }}">{{ $signalement->statutLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-front.pagination :items="$signalements" />
    @endif
@endsection
