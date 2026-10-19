@extends('layouts.admin')

@section('title', $certification->numero)

@section('content')
    <x-admin.page-header :title="$certification->numero" module="Module 5 · Certification"
                         :subtitle="$certification->typeLabel() . ' · ' . $certification->produit?->nom" :back="route('admin.certifications.index')">
        <x-slot:actions>
            <a href="{{ route('front.certifications.index', ['numero' => $certification->numero]) }}" class="btn btn-light" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Vue publique
            </a>
            @if ($certification->statut === 'en_attente')
                <form method="POST" action="{{ route('admin.certifications.valider', $certification) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg mr-1" aria-hidden="true"></i> Valider</button>
                </form>
                <form method="POST" action="{{ route('admin.certifications.suspendre', $certification) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-warning"><i class="bi bi-pause-circle mr-1" aria-hidden="true"></i> Suspendre</button>
                </form>
            @elseif ($certification->statut === 'valide')
                <form method="POST" action="{{ route('admin.certifications.suspendre', $certification) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-warning"><i class="bi bi-pause-circle mr-1" aria-hidden="true"></i> Suspendre</button>
                </form>
            @elseif ($certification->statut === 'suspendue')
                <form method="POST" action="{{ route('admin.certifications.valider', $certification) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success"><i class="bi bi-play-circle mr-1" aria-hidden="true"></i> Réactiver</button>
                </form>
            @endif
            <a href="{{ route('admin.certifications.edit', $certification) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Label</h2></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="nt-thumb nt-thumb-placeholder mr-3" style="width: 3rem; height: 3rem; font-size: 1.4rem;">
                            <i class="bi {{ $certification->icone() }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="h5 mb-1">{{ $certification->typeLabel() }}</div>
                            @include('pages.admin.certifications._statut')
                        </div>
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Numéro</span><strong>{{ $certification->numero }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Obtenue le</span><strong>{{ $certification->date_obtention?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Expire le</span><strong>{{ $certification->date_expiration?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Validité</span>
                        @if ($certification->estValide())
                            <span class="text-success"><i class="bi bi-patch-check-fill mr-1" aria-hidden="true"></i>{{ $certification->joursRestants() }} jour(s) restant(s)</span>
                        @else
                            <span class="text-danger"><i class="bi bi-x-octagon-fill mr-1" aria-hidden="true"></i>Non valide</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="nt-card-title">Organisme certificateur</h2></div>
                        <div class="card-body">
                            @if ($certification->organisme)
                                <div class="nt-role-row">
                                    <span class="text-muted">Nom</span>
                                    <a href="{{ route('admin.organismes.show', $certification->organisme) }}" class="nt-badge">{{ $certification->organisme->nom }}</a>
                                </div>
                                <div class="nt-role-row"><span class="text-muted">Pays</span><strong>{{ $certification->organisme->pays }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Accréditation</span><strong>{{ $certification->organisme->accreditation }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="nt-card-title">Produit certifié</h2></div>
                        <div class="card-body">
                            @if ($certification->produit)
                                <div class="nt-role-row">
                                    <span class="text-muted">Produit</span>
                                    <a href="{{ route('admin.produits.show', $certification->produit) }}" class="nt-badge">{{ $certification->produit->nom }}</a>
                                </div>
                                <div class="nt-role-row"><span class="text-muted">Catégorie</span><strong>{{ $certification->produit->categorie?->nom ?? '—' }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Origine</span><strong>{{ $certification->produit->origine }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Producteur / transformateur</span><strong>{{ $certification->produit->proprietaire?->name ?? 'Administration' }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- IA Module 5 --}}
            <div class="card mb-4" id="ia-panel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="nt-card-title"><i class="bi bi-robot mr-1" aria-hidden="true"></i> Vérification IA du document</h2>
                    @if ($certification->ia_verifie_at)
                        <small class="text-muted">Dernière analyse : {{ $certification->ia_verifie_at->diffForHumans() }}</small>
                    @endif
                </div>
                <div class="card-body">
                    {{-- Upload document --}}
                    <form method="POST" action="{{ route('admin.certifications.ia.upload', $certification) }}" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="col">
                                <label class="font-weight-600 small" for="document_pdf">Document certificat (.txt ou .docx)</label>
                                <input type="file" id="document_pdf" name="document_pdf" class="form-control-file mt-1" accept=".txt,.docx">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-upload mr-1" aria-hidden="true"></i> Joindre
                                </button>
                            </div>
                        </div>
                        @if ($certification->document_pdf)
                            <div class="mt-1 small text-success"><i class="bi bi-file-earmark-check mr-1" aria-hidden="true"></i>Document joint : {{ basename($certification->document_pdf) }}</div>
                        @endif
                    </form>

                    {{-- Boutons IA --}}
                    @if ($certification->document_pdf)
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-sm btn-primary" id="btn-verifier" onclick="iaVerifier()">
                                <i class="bi bi-shield-check mr-1" aria-hidden="true"></i> Vérifier l'authenticité
                            </button>
                            <button class="btn btn-sm btn-warning" id="btn-incoherences" onclick="iaIncoherences()">
                                <i class="bi bi-exclamation-triangle mr-1" aria-hidden="true"></i> Détecter les incohérences
                            </button>
                        </div>
                    @else
                        <p class="text-muted small mb-0"><i class="bi bi-info-circle mr-1" aria-hidden="true"></i>Joignez un document pour activer la vérification IA.</p>
                    @endif

                    {{-- Résultat IA --}}
                    <div id="ia-resultat" class="mt-3" style="display:none"></div>

                    {{-- Résultat précédent --}}
                    @if ($certification->ia_verification)
                        <div class="alert alert-light border small mt-2">
                            <strong><i class="bi bi-clock-history mr-1" aria-hidden="true"></i>Dernière vérification :</strong><br>
                            {{ $certification->ia_verification }}
                        </div>
                    @endif
                    @if ($certification->ia_incoherences)
                        <div class="alert alert-warning small mt-2">
                            <strong><i class="bi bi-exclamation-triangle mr-1" aria-hidden="true"></i>Incohérences détectées :</strong><br>
                            {{ $certification->ia_incoherences }}
                        </div>
                    @endif
                </div>
            </div>

            <x-admin.table-card :items="$autresLabels" title="Autres labels de ce produit">
                <x-slot:head>
                    <th scope="col">Numéro</th>
                    <th scope="col">Label</th>
                    <th scope="col">Organisme</th>
                    <th scope="col">Statut</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($autresLabels as $autre)
                    <tr>
                        <td class="nt-cell-title">{{ $autre->numero }}</td>
                        <td><span class="nt-badge"><i class="bi {{ $autre->icone() }}" aria-hidden="true"></i> {{ $autre->typeLabel() }}</span></td>
                        <td class="text-muted">{{ $autre->organisme?->nom }}</td>
                        <td>@include('pages.admin.certifications._statut', ['certification' => $autre])</td>
                        <td class="text-right"><x-admin.row-actions :show="route('admin.certifications.show', $autre)" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-award" title="Aucun autre label">
                        Ce produit ne porte que cette certification.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>

    @push('scripts')
    <script>
    const csrfToken = '{{ csrf_token() }}';
    const urlVerifier = '{{ route('admin.certifications.ia.verifier', $certification) }}';
    const urlIncoherences = '{{ route('admin.certifications.ia.incoherences', $certification) }}';

    function iaAfficher(html) {
        const el = document.getElementById('ia-resultat');
        el.innerHTML = html;
        el.style.display = '';
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function iaChargement(btnId) {
        document.getElementById(btnId).disabled = true;
        iaAfficher('<div class="text-muted small"><span class="spinner-border spinner-border-sm mr-1"></span> Analyse IA en cours…</div>');
    }

    function iaTermine(btnId) {
        document.getElementById(btnId).disabled = false;
    }

    async function iaVerifier() {
        iaChargement('btn-verifier');
        try {
            const r = await fetch(urlVerifier, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
            const d = await r.json();
            if (d.ok) {
                const icone = d.valide ? '✅' : '⚠️';
                const classe = d.valide ? 'success' : 'warning';
                iaAfficher(`<div class="alert alert-${classe} small">
                    <strong>${icone} Authenticité du document</strong><br>${d.resume}
                    <div class="mt-1 text-muted">Confiance : ${Math.round(d.confiance * 100)}%</div>
                </div>`);
            } else {
                iaAfficher(`<div class="alert alert-danger small">${d.message}</div>`);
            }
        } catch { iaAfficher('<div class="alert alert-danger small">Erreur réseau.</div>'); }
        iaTermine('btn-verifier');
    }

    async function iaIncoherences() {
        iaChargement('btn-incoherences');
        try {
            const r = await fetch(urlIncoherences, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
            const d = await r.json();
            if (d.ok) {
                const niveauClasse = { aucun: 'success', mineur: 'info', majeur: 'warning', critique: 'danger' }[d.niveau] || 'secondary';
                const liste = d.incoherences.length
                    ? '<ul class="mb-1 pl-3">' + d.incoherences.map(i => `<li>${i}</li>`).join('') + '</ul>'
                    : '<p class="mb-1">✅ Aucune incohérence trouvée.</p>';
                iaAfficher(`<div class="alert alert-${niveauClasse} small">
                    <strong>Niveau : ${d.niveau}</strong><br>${liste}
                    <em>${d.justification}</em>
                </div>`);
            } else {
                iaAfficher(`<div class="alert alert-danger small">${d.message}</div>`);
            }
        } catch { iaAfficher('<div class="alert alert-danger small">Erreur réseau.</div>'); }
        iaTermine('btn-incoherences');
    }
    </script>
    @endpush
@endsection
