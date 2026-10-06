@php($premiere = reset($tendances))

<x-admin.dashboard.panel titre="Évolution de la traçabilité" id="titre-tendance" class="flex-fill nt-panel-grow"
                         :sous-titre="'Événements enregistrés ' . $periode->granulariteLibelle() . ', comparés à la période précédente'">
    <x-slot:actions>
        <label class="sr-only" for="metrique">Métrique affichée</label>
        <select class="custom-select custom-select-sm nt-metric-select" id="metrique" data-metrique>
            @foreach ($tendances as $cle => $tendance)
                <option value="{{ $cle }}">{{ $tendance['libelle'] }}</option>
            @endforeach
        </select>
    </x-slot:actions>

    <div class="nt-trend-summary">
        <div>
            <span class="nt-trend-total" data-tendance-total>{{ \App\Support\TableauDeBord::nombre($premiere['total']) }}</span>
            <span class="nt-trend-caption" data-tendance-legende>{{ $premiere['legende'] }} sur la période</span>
        </div>
        <ul class="nt-legend nt-legend-inline" aria-hidden="true">
            <li><span class="nt-key nt-key-line"></span>Période actuelle</li>
            <li><span class="nt-key nt-key-dashed"></span>Période précédente</li>
        </ul>
    </div>

    <div class="nt-chart nt-chart-lg">
        <canvas id="chart-tendance" role="img"
                aria-label="{{ $premiere['libelle'] }} : {{ $premiere['total'] }} sur la période, contre {{ $premiere['totalPrecedent'] }} la période précédente"></canvas>
        <p class="nt-chart-empty" data-chart-empty hidden>
            <i class="bi bi-bar-chart" aria-hidden="true"></i>
            Aucune donnée disponible pour cette période.
        </p>
    </div>
</x-admin.dashboard.panel>
