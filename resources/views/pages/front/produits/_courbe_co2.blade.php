{{--
    _courbe_co2.blade.php — Module 1 (Ghada)
    Courbe SVG du CO₂ par lot dans le temps.
    Affichée seulement si $lotsAvecScore contient ≥ 2 lots.
    Variables : $lotsAvecScore (Collection de Lot avec empreinteCarbone chargée).

    Bandes A→E en arrière-plan, réutilise les classes eco-a/b/c/d/e du template.
    Seuils (kg CO₂e) : A < 1, B < 2, C < 4, D < 7, E ≥ 7.
--}}
@if ($lotsAvecScore->count() >= 2)

@php
    /* Trier par date de production croissante */
    $lotsOrdres = $lotsAvecScore
        ->sortBy(fn ($l) => $l->date_production?->timestamp ?? 0)
        ->values();

    /* Valeurs CO₂ */
    $valeurs = $lotsOrdres->map(fn ($l) => (float) $l->empreinteCarbone->co2_total);
    $co2Max  = max($valeurs->max(), 7.5); /* au moins jusqu'à la bande E */
    $co2Min  = 0.0;

    /* Dimensions SVG */
    $svgW    = 600;
    $svgH    = 220;
    $padL    = 48;
    $padR    = 16;
    $padT    = 12;
    $padB    = 36;
    $plotW   = $svgW - $padL - $padR;
    $plotH   = $svgH - $padT - $padB;
    $n       = $lotsOrdres->count();

    /* Mapping valeur → coordonnée */
    $xOf = fn (int $i) => $padL + ($n === 1 ? $plotW / 2 : $i / ($n - 1) * $plotW);
    $yOf = fn (float $v) => $padT + $plotH - ($v - $co2Min) / max($co2Max - $co2Min, 0.01) * $plotH;

    /* Seuils des bandes (max exclus) */
    $bandes = [
        ['cls' => 'eco-a', 'min' => 0,   'max' => 1,   'label' => 'A'],
        ['cls' => 'eco-b', 'min' => 1,   'max' => 2,   'label' => 'B'],
        ['cls' => 'eco-c', 'min' => 2,   'max' => 4,   'label' => 'C'],
        ['cls' => 'eco-d', 'min' => 4,   'max' => 7,   'label' => 'D'],
        ['cls' => 'eco-e', 'min' => 7,   'max' => null, 'label' => 'E'],
    ];

    /* Polyline points */
    $points = $lotsOrdres->keys()->map(fn ($i) => round($xOf($i), 1) . ',' . round($yOf($valeurs[$i]), 1))->implode(' ');

    /* Tendance */
    $dernierCo2    = $valeurs->last();
    $avantDernierCo2 = $valeurs->slice(-2, 1)->first();
    $delta         = $dernierCo2 - $avantDernierCo2;
    $tendanceClass = match (true) {
        $delta < -0.05 => 'm1-tendance--amelioration',
        $delta > 0.05  => 'm1-tendance--degradation',
        default        => 'm1-tendance--stable',
    };
    $tendanceIcone = match (true) {
        $delta < -0.05 => 'bi-arrow-down-circle-fill',
        $delta > 0.05  => 'bi-arrow-up-circle-fill',
        default        => 'bi-dash-circle-fill',
    };
    $tendanceTexte = match (true) {
        $delta < -0.05 => 'En amélioration (' . number_format(abs($delta), 2, ',', ' ') . ' kg CO₂e de moins)',
        $delta > 0.05  => 'En dégradation (+' . number_format($delta, 2, ',', ' ') . ' kg CO₂e)',
        default        => 'Stable',
    };
@endphp

<div class="panel mb-4">
    <h2 class="panel-title">
        <i class="bi bi-graph-up text-success" aria-hidden="true"></i>Évolution du CO₂ par lot
    </h2>

    <svg class="m1-courbe-svg" viewBox="0 0 {{ $svgW }} {{ $svgH }}"
         role="img" aria-label="Courbe du CO₂ moyen par lot"
         xmlns="http://www.w3.org/2000/svg">

        {{-- Bandes A→E en arrière-plan --}}
        @foreach ($bandes as $bande)
            @php
                $bandMin = (float) $bande['min'];
                $bandMax = $bande['max'] !== null ? (float) $bande['max'] : $co2Max;
                $bandMax = min($bandMax, $co2Max);
                if ($bandMin >= $co2Max) continue;
                $by1  = round($yOf(min($bandMax, $co2Max)), 1);
                $by2  = round($yOf($bandMin), 1);
                $bH   = max($by2 - $by1, 0);
                $midY = round(($by1 + $by2) / 2, 1);
            @endphp
            <rect x="{{ $padL }}" y="{{ $by1 }}"
                  width="{{ $plotW }}" height="{{ $bH }}"
                  class="{{ $bande['cls'] }}" opacity="0.15" />
            <text x="{{ $padL - 6 }}" y="{{ $midY }}"
                  class="m1-courbe-bande-label"
                  text-anchor="end"
                  fill="currentColor"
                  font-size="10"
                  dominant-baseline="middle">{{ $bande['label'] }}</text>
        @endforeach

        {{-- Grille horizontale légère --}}
        @foreach ([1, 2, 4, 7] as $seuil)
            @if ($seuil <= $co2Max)
                @php $gy = round($yOf((float) $seuil), 1) @endphp
                <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $padL + $plotW }}" y2="{{ $gy }}"
                      stroke="#ccc" stroke-width="0.5" stroke-dasharray="4,3" />
            @endif
        @endforeach

        {{-- Zone remplie sous la courbe --}}
        @php
            $firstX = round($xOf(0), 1);
            $lastX  = round($xOf($n - 1), 1);
            $baseY  = $padT + $plotH;
            $areaPoints = $firstX . ',' . $baseY . ' ' . $points . ' ' . $lastX . ',' . $baseY;
        @endphp
        <polygon points="{{ $areaPoints }}" fill="#087443" opacity="0.08" />

        {{-- Ligne de la courbe --}}
        <polyline points="{{ $points }}"
                  fill="none"
                  stroke="#087443"
                  stroke-width="2.5"
                  stroke-linejoin="round"
                  stroke-linecap="round" />

        {{-- Points avec tooltip natif --}}
        @foreach ($lotsOrdres as $i => $lot)
            @php
                $cx  = round($xOf($i), 1);
                $cy  = round($yOf($valeurs[$i]), 1);
                $tip = $lot->numero_lot . ' · ' . number_format($valeurs[$i], 2, ',', ' ') . ' kg CO₂e';
            @endphp
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="5"
                    fill="#087443" stroke="#fff" stroke-width="2">
                <title>{{ $tip }}</title>
            </circle>
            {{-- Label date sous l'axe --}}
            <text x="{{ $cx }}" y="{{ $padT + $plotH + 18 }}"
                  text-anchor="middle"
                  fill="#65756a"
                  font-size="9">
                {{ $lot->date_production?->format('m/Y') ?? '' }}
            </text>
        @endforeach

        {{-- Axe Y (unité) --}}
        <text x="{{ $padL - 4 }}" y="{{ $padT + $plotH / 2 }}"
              fill="#65756a" font-size="9" text-anchor="middle"
              transform="rotate(-90, {{ $padL - 22 }}, {{ $padT + $plotH / 2 }})">
            kg CO₂e/u
        </text>
    </svg>

    {{-- Tendance --}}
    <div>
        <span class="m1-tendance {{ $tendanceClass }}">
            <i class="bi {{ $tendanceIcone }}" aria-hidden="true"></i>
            {{ $tendanceTexte }}
        </span>
    </div>
</div>
@endif
