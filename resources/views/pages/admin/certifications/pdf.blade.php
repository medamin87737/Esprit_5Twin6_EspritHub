<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a2e1a; background: #fff; font-size: 13px; }

        .header { background: #1a3a2a; color: #fff; padding: 28px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        .header .sub { font-size: 11px; opacity: .8; margin-top: 4px; }

        .badge-type { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;
            background: #d4edda; color: #1a3a2a; margin-top: 6px; }

        .body { padding: 36px 40px; }

        .title-section { text-align: center; margin-bottom: 32px; }
        .title-section h2 { font-size: 18px; color: #1a3a2a; text-transform: uppercase; letter-spacing: 2px; }
        .title-section .numero { font-size: 28px; font-weight: bold; color: #2d6a4f; margin: 8px 0; }
        .title-section .statut { font-size: 13px; }

        .section { margin-bottom: 24px; }
        .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; margin-bottom: 12px; }

        .row { display: flex; margin-bottom: 8px; }
        .row .label { width: 180px; color: #555; font-size: 12px; }
        .row .value { font-weight: bold; font-size: 12px; }

        .validity-box { background: #f0faf4; border: 2px solid #2d6a4f; border-radius: 8px; padding: 16px 20px; text-align: center; margin: 24px 0; }
        .validity-box .valid { font-size: 16px; font-weight: bold; color: #2d6a4f; }
        .validity-box .invalid { font-size: 16px; font-weight: bold; color: #c0392b; }
        .validity-box .dates { font-size: 11px; color: #555; margin-top: 6px; }

        .footer { border-top: 1px solid #e0e0e0; padding: 16px 40px; font-size: 10px; color: #888; text-align: center; }
        .footer strong { color: #1a3a2a; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <div style="font-size:14px; font-weight:bold; letter-spacing:2px;">🌿 NUTRITRACE</div>
        <div class="sub">Plateforme de traçabilité alimentaire</div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:11px; opacity:.7;">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="body">

    <div class="title-section">
        <h2>Certificat de Label</h2>
        <div class="numero">{{ $certification->numero }}</div>
        <span class="badge-type">{{ $certification->typeLabel() }}</span>
        <div class="statut" style="margin-top:8px; color: {{ $certification->estValide() ? '#2d6a4f' : '#c0392b' }}">
            {{ $certification->statutLabel() }}
        </div>
    </div>

    <div class="validity-box">
        @if ($certification->estValide())
            <div class="valid">✓ Certification valide</div>
            <div class="dates">
                Valable du {{ $certification->date_obtention?->format('d/m/Y') }}
                au {{ $certification->date_expiration?->format('d/m/Y') }}
                @if ($certification->joursRestants() !== null)
                    — {{ $certification->joursRestants() }} jours restants
                @endif
            </div>
        @else
            <div class="invalid">✗ Certification non valide ({{ $certification->statutLabel() }})</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Produit certifié</div>
        <div class="row"><span class="label">Nom du produit</span><span class="value">{{ $certification->produit?->nom ?? '—' }}</span></div>
        <div class="row"><span class="label">Catégorie</span><span class="value">{{ $certification->produit?->categorie?->nom ?? '—' }}</span></div>
        <div class="row"><span class="label">Origine</span><span class="value">{{ $certification->produit?->origine ?? '—' }}</span></div>
        <div class="row"><span class="label">Producteur</span><span class="value">{{ $certification->produit?->proprietaire?->name ?? 'Administration' }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">Organisme certificateur</div>
        <div class="row"><span class="label">Organisme</span><span class="value">{{ $certification->organisme?->nom ?? '—' }}</span></div>
        <div class="row"><span class="label">Pays</span><span class="value">{{ $certification->organisme?->pays ?? '—' }}</span></div>
        <div class="row"><span class="label">Accréditation</span><span class="value">{{ $certification->organisme?->accreditation ?? '—' }}</span></div>
        @if ($certification->organisme?->site_web)
            <div class="row"><span class="label">Site web</span><span class="value">{{ $certification->organisme->site_web }}</span></div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Dates</div>
        <div class="row"><span class="label">Date d'obtention</span><span class="value">{{ $certification->date_obtention?->format('d/m/Y') }}</span></div>
        <div class="row"><span class="label">Date d'expiration</span><span class="value">{{ $certification->date_expiration?->format('d/m/Y') }}</span></div>
    </div>

</div>

<div class="footer">
    Document généré par <strong>NutriTrace</strong> —
    Vérifiez l'authenticité sur <strong>{{ config('app.url') }}/certifications?numero={{ $certification->numero }}</strong>
</div>

</body>
</html>
