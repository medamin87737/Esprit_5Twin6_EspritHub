@php
    $texteResume = ((string) $analyseResumeeId === (string) $analyse->id && filled($resumeAffiche))
        ? $resumeAffiche
        : $analyse->resume_ia;
    $nouveauResume = (string) $analyseResumeeId === (string) $analyse->id && filled($resumeAffiche);
    $titreResume = 'Synthèse qualité · '.$analyse->typeLabel();
    $peutTelecharger = auth()->user()?->can('telechargerRapport', $analyse);
    $tone = $analyse->resultat === 'conforme' ? 'ok' : ($analyse->resultat === 'non_conforme' ? 'ko' : 'wait');
@endphp

<article class="nt-q-mini nt-q-mini--analyse nt-q-mini--{{ $tone }}">
    <div class="nt-q-mini__icon" aria-hidden="true">
        <i class="bi {{ $analyse->icone() }}"></i>
    </div>
    <h2 class="nt-q-mini__title">{{ $analyse->typeLabel() }}</h2>
    <p class="nt-q-mini__status">
        <span class="nt-badge nt-badge--{{ $tone }}">{{ $analyse->resultatLabel() }}</span>
    </p>
    <p class="nt-q-mini__meta">
        {{ $analyse->laboratoire?->nom ?? 'Laboratoire' }}
        · {{ $analyse->date_prelevement?->format('d/m/Y') ?? '—' }}
        @if ($analyse->numero)
            · {{ $analyse->numero }}
        @endif
    </p>

    @if ($texteResume)
        <div
            hidden
            data-nt-resume-popup
            data-texte="{{ e($texteResume) }}"
            data-titre="{{ e($titreResume) }}"
            @if ($nouveauResume) data-auto-open="1" @endif
        ></div>
    @endif

    <form method="POST" action="{{ route('consommateur.qualite.resumer', $analyse) }}" data-nt-loading-form>
        @csrf
        <button type="submit" class="nt-q-btn nt-q-btn--primary w-100">
            <i class="bi bi-robot" aria-hidden="true"></i>
            Synthèse IA
        </button>
    </form>
    @if ($peutTelecharger)
        <a href="{{ route('front.analyses.rapport', $analyse) }}" class="nt-q-btn nt-q-btn--ghost w-100 mt-2"
           title="{{ $analyse->rapport ? 'Télécharger le rapport du laboratoire' : 'Télécharger le rapport qualité généré' }}">
            <i class="bi bi-download" aria-hidden="true"></i>
            Télécharger le rapport
        </a>
    @endif
</article>
