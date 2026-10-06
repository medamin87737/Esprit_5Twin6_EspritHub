@php($niveaux = ['critique' => 'Critique', 'attention' => 'Attention', 'info' => 'À compléter'])

<x-admin.dashboard.panel titre="Points d'attention" id="titre-alertes" class="flex-fill"
                         :sous-titre="empty($alertes) ? 'Contrôles effectués sur toutes les données' : count($alertes) . ' point' . (count($alertes) > 1 ? 's' : '') . ' détecté' . (count($alertes) > 1 ? 's' : '') . ' dans les données'">
    @if (empty($alertes))
        <div class="nt-all-clear">
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            <div>
                <strong>Tout est conforme</strong>
                <p>Aucune analyse non conforme, échéance proche ou donnée manquante.</p>
            </div>
        </div>
    @else
        <ul class="nt-alerts">
            @foreach ($alertes as $alerte)
                <li class="nt-alert nt-alert-{{ $alerte['niveau'] }}">
                    <span class="nt-alert-icon"><i class="bi {{ $alerte['icone'] }}" aria-hidden="true"></i></span>
                    <div class="nt-alert-body">
                        <div class="nt-alert-head">
                            <strong class="nt-alert-title">{{ $alerte['titre'] }}</strong>
                            <span class="nt-alert-level">{{ $niveaux[$alerte['niveau']] }}</span>
                        </div>
                        <p class="nt-alert-text">{{ $alerte['description'] }}</p>
                        <div class="nt-alert-meta">
                            @if ($alerte['date'])
                                <span>{{ $alerte['dateLibelle'] }} : {{ $alerte['date']->translatedFormat('j M Y') }}</span>
                            @endif
                            @if ($alerte['action'])
                                <a class="nt-alert-action" href="{{ $alerte['action']['url'] }}">
                                    {{ $alerte['action']['libelle'] }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-admin.dashboard.panel>
