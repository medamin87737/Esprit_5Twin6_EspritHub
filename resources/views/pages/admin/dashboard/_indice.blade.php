<x-admin.dashboard.panel titre="Indice de traçabilité" id="titre-indice"
                         sous-titre="Part moyenne des lots dotés d'un parcours, d'une empreinte et d'une analyse">
    @if ($indice === null)
        <div class="nt-panel-empty">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>
            <p>Données indisponibles : aucun lot enregistré.</p>
        </div>
    @else
        <div class="nt-index">
            <x-admin.dashboard.jauge :score="$indice['score']" :niveau="$indice['niveau']" />

            <ul class="nt-index-criteria">
                @foreach ($indice['criteres'] as $critere)
                    <li>
                        <div class="nt-index-criteria-head">
                            <span>{{ $critere['libelle'] }}</span>
                            <span><strong>{{ $critere['total'] }}</strong>/{{ $indice['lots'] }}</span>
                        </div>
                        <div class="progress nt-progress" role="progressbar" aria-label="{{ $critere['libelle'] }}"
                             aria-valuenow="{{ $critere['pourcentage'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $critere['pourcentage'] }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-admin.dashboard.panel>
