<header class="nt-dash-header">
    <div>
        <span class="nt-eyebrow">Tableau de bord</span>
        <h2 class="nt-page-title">Vue d'ensemble</h2>
        <p class="nt-page-subtitle">Suivez l'activité, la traçabilité et les indicateurs clés de NutriTrace.</p>
    </div>

    <form class="nt-dash-filters" method="GET" action="{{ route('admin.dashboard') }}" data-periode-form>
        <div class="nt-select-icon">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <label class="sr-only" for="periode">Période analysée</label>
            <select class="custom-select" id="periode" name="periode">
                @foreach (\App\Support\Periode::CHOIX as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($periode->cle === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="nt-dash-dates" data-dates>
            <label class="sr-only" for="du">Du</label>
            <input class="form-control" type="date" id="du" name="du" value="{{ $periode->cle === 'perso' ? $periode->debut->toDateString() : '' }}">
            <span aria-hidden="true">→</span>
            <label class="sr-only" for="au">Au</label>
            <input class="form-control" type="date" id="au" name="au" value="{{ $periode->cle === 'perso' ? $periode->fin->toDateString() : '' }}">
        </div>

        <button class="btn btn-primary" type="submit">Appliquer</button>
        <a class="nt-action nt-action-lg" href="{{ request()->fullUrl() }}" data-toggle="tooltip" title="Actualiser les données">
            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span class="sr-only">Actualiser les données</span>
        </a>
    </form>

    <p class="nt-dash-period">
        <i class="bi bi-clock-history" aria-hidden="true"></i>
        <strong>{{ $periode->libelle() }}</strong>
        <span>· période précédente : {{ $precedente->libelle() }}</span>
    </p>
</header>
