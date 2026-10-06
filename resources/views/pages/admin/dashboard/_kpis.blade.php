<section class="nt-panel nt-kpi-band mb-4" aria-label="Indicateurs clés de la période">
    @foreach ($kpis as $kpi)
        <a class="nt-kpi-item" href="{{ $kpi['lien'] }}">
            <span class="nt-kpi-item-label">{{ $kpi['libelle'] }}</span>
            <span class="nt-kpi-item-value">
                {{ $kpi['affichage'] }}
                @if ($kpi['unite'])
                    <small>{{ $kpi['unite'] }}</small>
                @endif
            </span>
            <x-admin.dashboard.variation :courant="$kpi['valeur']" :precedent="$kpi['precedent']" :inverse="$kpi['inverse']" />
            <span class="nt-kpi-item-context">{{ $kpi['contexte'] }}</span>
            <x-admin.dashboard.sparkline :valeurs="$kpi['serie']" />
        </a>
    @endforeach
</section>
