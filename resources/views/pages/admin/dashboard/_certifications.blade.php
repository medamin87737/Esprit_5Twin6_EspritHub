<x-admin.dashboard.panel titre="Certifications vérifiées" id="titre-certifications" class="flex-fill"
                         sous-titre="Tous les labels enregistrés, par type et statut actuel">
    <x-slot:actions>
        <a class="btn btn-light btn-sm" href="{{ route('admin.certifications.index') }}">Détails</a>
    </x-slot:actions>

    @if ($certifications['total'] === 0)
        <div class="nt-panel-empty">
            <i class="bi bi-patch-check" aria-hidden="true"></i>
            <p>Aucune certification enregistrée pour le moment.</p>
        </div>
    @else
        <p class="nt-panel-headline">
            <strong>{{ $certifications['valides'] }}</strong> valide{{ $certifications['valides'] > 1 ? 's' : '' }}
            <span>sur {{ $certifications['total'] }} certification{{ $certifications['total'] > 1 ? 's' : '' }}
                ({{ round($certifications['valides'] / $certifications['total'] * 100) }} %)</span>
        </p>

        <div class="nt-chart" style="height: {{ 70 + count($certifications['types']) * 46 }}px;">
            <canvas id="chart-certifications" role="img" aria-describedby="table-certifications"
                    aria-label="Certifications par type et par statut"></canvas>
        </div>

        <ul class="nt-legend nt-legend-inline mt-3">
            @foreach ($certifications['statuts'] as $statut)
                <li><span class="nt-key" style="--key: {{ $statut['couleur'] }}"></span>{{ $statut['libelle'] }}</li>
            @endforeach
        </ul>

        <div class="sr-only">
            <table id="table-certifications">
                <caption>Nombre de certifications par type et par statut</caption>
                <thead>
                    <tr>
                        <th scope="col">Type</th>
                        @foreach ($certifications['statuts'] as $statut)
                            <th scope="col">{{ $statut['libelle'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certifications['types'] as $i => $type)
                        <tr>
                            <th scope="row">{{ $type }}</th>
                            @foreach ($certifications['statuts'] as $statut)
                                <td>{{ $statut['valeurs'][$i] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.dashboard.panel>
