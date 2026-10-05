@extends('layouts.pro')

@section('title', 'Mes certifications')

@section('pro_content')
    @if ($expirations > 0)
        <div class="notice-panel mb-4" role="alert">
            <i class="bi bi-alarm" aria-hidden="true"></i>
            <div><strong>{{ $expirations }} certification(s)</strong> expire(nt) dans moins de 30 jours : pensez à les renouveler auprès de l'organisme.</div>
        </div>
    @endif

    <div class="pro-toolbar">
        <form method="GET" action="{{ route('pro.certifications.index') }}" class="d-flex gap-2">
            <label for="statut" class="visually-hidden">Statut</label>
            <select class="form-select" id="statut" name="statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach (config('nutritrace.options.certification_statuts') as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-outline-primary" type="submit">Filtrer</button></noscript>
        </form>
        <a href="{{ route('pro.certifications.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Déclarer une certification</a>
    </div>

    @if ($certifications->isEmpty())
        <x-front.empty-state icon="bi-award" title="Aucune certification" :reset="request()->filled('statut') ? route('pro.certifications.index') : null">
            Déclarez les labels de vos produits (bio, local, équitable) : ils seront publiés après vérification.
        </x-front.empty-state>
    @else
        <div class="data-card">
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Label</th>
                            <th scope="col">Produit</th>
                            <th scope="col">Organisme</th>
                            <th scope="col">Obtention</th>
                            <th scope="col">Expiration</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($certifications as $certification)
                            <tr>
                                <td>
                                    <x-front.certification-badge :certification="$certification" />
                                    <div class="cell-sub mt-1">n° {{ $certification->numero }}</div>
                                </td>
                                <td class="cell-title">{{ $certification->produit?->nom }}</td>
                                <td>{{ $certification->organisme?->nom }}</td>
                                <td>{{ $certification->date_obtention?->format('d/m/Y') }}</td>
                                <td>{{ $certification->date_expiration?->format('d/m/Y') }}</td>
                                <td class="text-nowrap">
                                    <span class="status-pill {{ $certification->statutClasse() }}">{{ $certification->statutLabel() }}</span>
                                    @if ($certification->expireBientot())
                                        <span class="status-pill status-expired" title="Expire dans moins de 30 jours"><i class="bi bi-alarm me-1" aria-hidden="true"></i>{{ $certification->joursRestants() }} j</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-front.pagination :items="$certifications" />
    @endif
@endsection
