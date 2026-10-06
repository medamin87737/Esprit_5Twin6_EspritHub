<x-admin.dashboard.panel titre="Impact environnemental" id="titre-environnement" class="flex-fill"
                         sous-titre="Empreinte carbone moyenne par unité, calculée à partir des indicateurs déclarés">
    <x-slot:actions>
        <a class="btn btn-light btn-sm" href="{{ route('admin.empreintes.index') }}">Détails</a>
    </x-slot:actions>

    @if ($environnement['moyenne'] === null)
        <div class="nt-panel-empty">
            <i class="bi bi-cloud" aria-hidden="true"></i>
            <p>Les données environnementales seront disponibles lorsque les calculs d'impact seront alimentés pour cette période.</p>
        </div>
    @else
        <div class="nt-env-summary">
            <div>
                <span class="nt-env-value">{{ \App\Support\TableauDeBord::nombre($environnement['moyenne'], 2) }}<small>kg CO₂e / unité</small></span>
                <x-admin.dashboard.variation :courant="$environnement['moyenne']" :precedent="$environnement['precedente']" :inverse="true" />
            </div>
            <span class="nt-score nt-score-{{ strtolower(\App\Models\EmpreinteCarbone::scorePour($environnement['moyenne'])) }}"
                  title="Score moyen">{{ \App\Models\EmpreinteCarbone::scorePour($environnement['moyenne']) }}</span>
        </div>

        <div class="nt-chart nt-chart-sm">
            <canvas id="chart-co2" role="img"
                    aria-label="Évolution de l'empreinte moyenne : {{ \App\Support\TableauDeBord::nombre($environnement['moyenne'], 2) }} kg CO₂e par unité en moyenne sur {{ $environnement['nombre'] }} empreintes"></canvas>
        </div>

        <div class="nt-score-row" role="group" aria-label="Répartition des scores des empreintes calculées sur la période">
            <span class="nt-score-row-label">{{ $environnement['nombre'] }} empreinte{{ $environnement['nombre'] > 1 ? 's' : '' }}</span>
            @foreach ($environnement['scores'] as $score => $total)
                <span class="nt-score-chip {{ $total ? '' : 'is-empty' }}">
                    <span class="nt-score nt-score-{{ strtolower($score) }}">{{ $score }}</span>
                    <span>{{ $total }}</span>
                </span>
            @endforeach
        </div>
    @endif
</x-admin.dashboard.panel>
