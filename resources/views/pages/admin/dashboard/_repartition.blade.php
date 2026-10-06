<x-admin.dashboard.panel titre="Répartition des lots" id="titre-repartition" class="flex-fill"
                         sous-titre="Situation actuelle, selon la dernière étape enregistrée">
    @if ($repartition['total'] === 0)
        <div class="nt-panel-empty">
            <i class="bi bi-boxes" aria-hidden="true"></i>
            <p>Aucun lot enregistré pour le moment.</p>
        </div>
    @else
        <div class="nt-donut">
            <div class="nt-chart nt-chart-donut">
                <canvas id="chart-repartition" role="img"
                        aria-label="Répartition de {{ $repartition['total'] }} lots : {{ collect($repartition['segments'])->map(fn ($s) => $s['libelle'] . ' ' . $s['total'])->implode(', ') }}"></canvas>
                <div class="nt-donut-center" aria-hidden="true">
                    <span class="nt-donut-value">{{ \App\Support\TableauDeBord::nombre($repartition['total']) }}</span>
                    <span class="nt-donut-label">lots</span>
                </div>
            </div>

            <ul class="nt-legend">
                @foreach ($repartition['segments'] as $segment)
                    <li>
                        <span class="nt-key" style="--key: {{ $segment['couleur'] }}"></span>
                        <span class="nt-legend-label">{{ $segment['libelle'] }}</span>
                        <span class="nt-legend-value">{{ $segment['total'] }}</span>
                        <span class="nt-legend-share">{{ round($segment['total'] / $repartition['total'] * 100) }} %</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-admin.dashboard.panel>
