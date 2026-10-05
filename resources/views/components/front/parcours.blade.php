@props([
    'etapes',
    'indicateurs' => false,
    'actions' => false,
])

<ol {{ $attributes->class(['timeline parcours']) }}>
    @foreach ($etapes as $etape)
        <li class="timeline-step">
            <div class="timeline-marker" aria-hidden="true">
                <i class="bi {{ $etape->icone() }}"></i>
                <span class="timeline-number">{{ $loop->iteration }}</span>
            </div>
            <div class="timeline-content">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <h3 class="timeline-title mb-0"><span class="visually-hidden">Étape {{ $loop->iteration }} : </span>{{ $etape->typeLabel() }}</h3>
                    <span class="status-pill status-valid"><i class="bi bi-calendar-event" aria-hidden="true"></i> {{ $etape->date_heure?->format('d/m/Y à H:i') }}</span>
                </div>

                <ul class="parcours-infos">
                    <li><i class="bi bi-building" aria-hidden="true"></i>{{ $etape->acteur?->nom }}@if ($etape->acteur?->typeActeur) <span class="text-muted">· {{ $etape->acteur->typeActeur->libelle }}</span>@endif</li>
                    <li><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $etape->lieu }}</li>
                    <li><i class="bi bi-truck" aria-hidden="true"></i>Transport : {{ $etape->transportLabel() }}</li>
                </ul>

                @if ($etape->remarques)
                    <p class="timeline-text mb-0"><i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>{{ $etape->remarques }}</p>
                @endif

                @if ($indicateurs && $etape->indicateurs->isNotEmpty())
                    <div class="parcours-indicateurs">
                        @foreach ($etape->indicateurs as $indicateur)
                            <span class="indicateur-chip"><i class="bi {{ $indicateur->icone() }}" aria-hidden="true"></i>{{ $indicateur->typeLabel() }} : <strong>{{ number_format($indicateur->valeur, 2, ',', ' ') }} {{ $indicateur->unite }}</strong></span>
                        @endforeach
                    </div>
                @endif

                @if ($actions)
                    <div class="parcours-actions">
                        @can('update', $etape)
                            <a href="{{ route('pro.indicateurs.create', $etape) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Ajouter un indicateur</a>
                            <a href="{{ route('pro.etapes.edit', $etape) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Modifier</a>
                            <form method="POST" action="{{ route('pro.etapes.destroy', $etape) }}" class="d-inline"
                                  onsubmit="return confirm('Supprimer cette étape et ses indicateurs ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i>Supprimer</button>
                            </form>
                        @else
                            <span class="small text-muted"><i class="bi bi-lock me-1" aria-hidden="true"></i>Étape verrouillée</span>
                        @endcan
                    </div>
                @endif
            </div>
        </li>
    @endforeach
</ol>
