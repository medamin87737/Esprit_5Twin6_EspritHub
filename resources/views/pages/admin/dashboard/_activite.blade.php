<x-admin.dashboard.panel titre="Activité récente" id="titre-activite" class="flex-fill nt-panel-flush"
                         sous-titre="Derniers événements enregistrés sur la période">
    <x-slot:actions>
        <a class="btn btn-light btn-sm" href="{{ route('admin.etapes.index') }}">Toutes les étapes</a>
        <a class="btn btn-light btn-sm" href="{{ route('admin.analyses.index') }}">Toutes les analyses</a>
    </x-slot:actions>

    @if ($activite->isEmpty())
        <div class="nt-panel-empty">
            <i class="bi bi-activity" aria-hidden="true"></i>
            <p>Aucune activité enregistrée sur cette période.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table nt-table nt-activity-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Type</th>
                        <th scope="col">Acteur</th>
                        <th scope="col">Lot / produit</th>
                        <th scope="col">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activite as $ligne)
                        <tr>
                            <td class="nt-activity-date">
                                {{ $ligne['date']?->translatedFormat('j M') }}
                                @if ($ligne['heure'])
                                    <span class="nt-cell-sub">{{ $ligne['date']->format('H:i') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="nt-activity-type">
                                    <i class="bi {{ $ligne['icone'] }}" aria-hidden="true"></i>
                                    @if ($ligne['lien'])
                                        <a href="{{ $ligne['lien'] }}">{{ $ligne['type'] }}</a>
                                    @else
                                        {{ $ligne['type'] }}
                                    @endif
                                </span>
                            </td>
                            <td>{{ $ligne['acteur'] ?? '—' }}</td>
                            <td class="nt-activity-object">{{ $ligne['objet'] ?? '—' }}</td>
                            <td><span class="{{ $ligne['badge'] }}">{{ $ligne['statut'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.dashboard.panel>
