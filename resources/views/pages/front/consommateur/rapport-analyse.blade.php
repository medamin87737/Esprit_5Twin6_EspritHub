<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport {{ $analyse->numero }} — NutriTrace</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #eef3f0; color: #202b28; }
        .page { max-width: 820px; margin: 32px auto; background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 18px 50px rgba(6,78,53,.18); }
        header { background: linear-gradient(120deg, #064e35, #0a8f52); color: #fff; padding: 28px 36px; }
        header .marque { font-size: .72rem; letter-spacing: .14em; font-weight: 800; text-transform: uppercase; color: #ffd97a; }
        header h1 { margin: 6px 0 2px; font-size: 1.6rem; }
        header p { margin: 0; color: #c8f0de; }
        .verdict { margin: 24px 36px 0; border-radius: 12px; padding: 16px 20px; font-size: 1.05rem; }
        .verdict.conforme { background: #e5f6ec; color: #0b5d33; border: 1px solid #b5e3c8; }
        .verdict.non_conforme { background: #fdecec; color: #8c1d18; border: 1px solid #f5c2c0; }
        .verdict.en_attente { background: #fff6dd; color: #7a5b00; border: 1px solid #f0d78c; }
        main { padding: 8px 36px 10px; }
        h2 { font-size: 1.02rem; color: #064e35; margin: 22px 0 8px; border-bottom: 2px solid #e5f6ec; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 4px; border-bottom: 1px solid #eef2ef; font-size: .94rem; }
        td.k { color: #65756a; width: 38%; }
        .section { border-left: 5px solid #087f5b; background: #f4faf6; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; }
        .section h3 { margin: 0 0 4px; font-size: .82rem; text-transform: uppercase; letter-spacing: .04em; color: #0b5d33; }
        .section p { margin: 0; line-height: 1.6; font-size: .94rem; }
        footer { padding: 18px 36px 26px; color: #8a978d; font-size: .8rem; display: flex; justify-content: space-between; }
        @media print { body { background: #fff; } .page { box-shadow: none; margin: 0; } }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div class="marque">NutriTrace · Rapport qualité</div>
            <h1>{{ $analyse->typeLabel() }} — {{ $analyse->numero }}</h1>
            <p>{{ $analyse->lot?->produit?->nom }} · Lot {{ $analyse->lot?->numero_lot }}</p>
        </header>

        @php
            $classe = $analyse->resultat === 'conforme' ? 'conforme' : ($analyse->resultat === 'non_conforme' ? 'non_conforme' : 'en_attente');
            $icone = $analyse->resultat === 'conforme' ? '✓' : ($analyse->resultat === 'non_conforme' ? '✕' : '…');
        @endphp
        <div class="verdict {{ $classe }}"><strong>{{ $icone }} Résultat : {{ $analyse->resultatLabel() }}</strong></div>

        <main>
            <h2>Informations de l'analyse</h2>
            <table>
                <tr><td class="k">Numéro d'analyse</td><td>{{ $analyse->numero }}</td></tr>
                <tr><td class="k">Type d'analyse</td><td>{{ $analyse->typeLabel() }}</td></tr>
                <tr><td class="k">Produit</td><td>{{ $analyse->lot?->produit?->nom }}</td></tr>
                <tr><td class="k">Lot</td><td>{{ $analyse->lot?->numero_lot }}</td></tr>
                <tr><td class="k">Laboratoire</td><td>{{ $analyse->laboratoire?->nom }}@if ($analyse->laboratoire?->ville) ({{ $analyse->laboratoire->ville }}) @endif</td></tr>
                <tr><td class="k">Date de prélèvement</td><td>{{ $analyse->date_prelevement?->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td class="k">Date du résultat</td><td>{{ $analyse->date_resultat?->format('d/m/Y') ?? '—' }}</td></tr>
                @if ($analyse->commentaire)
                    <tr><td class="k">Commentaire du laboratoire</td><td>{{ $analyse->commentaire }}</td></tr>
                @endif
            </table>

            @if ($analyse->resume_ia)
                <h2>Synthèse qualité</h2>
                @foreach (preg_split('/\n+/', trim($analyse->resume_ia)) as $ligne)
                    @if (trim($ligne) !== '')
                        <div class="section">
                            @if (str_contains($ligne, ' : '))
                                <h3>{{ strstr($ligne, ' : ', true) }}</h3>
                                <p>{{ trim(substr(strstr($ligne, ' : '), 3)) }}</p>
                            @else
                                <p>{{ $ligne }}</p>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        </main>

        <footer>
            <span>Document généré par NutriTrace le {{ now()->format('d/m/Y à H:i') }}</span>
            <span>{{ $analyse->numero }}</span>
        </footer>
    </div>
</body>
</html>
