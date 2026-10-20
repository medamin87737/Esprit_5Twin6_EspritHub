@extends('layouts.front')

@section('title', 'Vérifier un label')

@section('content')
    @php
        $certification = $certification ?? null;
        $certifications = $certifications ?? collect();
        $numero = trim((string) request('numero'));
        $types = config('nutritrace.options.certification_types');
        $statutClasses = ['valide' => 'status-valid', 'expiree' => 'status-expired', 'suspendue' => 'status-suspended'];
    @endphp

    <x-front.page-header eyebrow="Labels" title="Ce label est-il valide ?" image="consommateur-salade.webp"
                         subtitle="Vérifiez en quelques secondes qu'une certification bio, locale ou équitable est authentique et toujours en cours de validité.">
        <x-slot:actions>
            <a href="{{ route('front.certifications.chat') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-robot me-2" aria-hidden="true"></i> Poser une question à l'IA
            </a>
        </x-slot:actions>
    </x-front.page-header>

    {{-- Bouton chatbot IA --}}
    <div class="text-center" style="margin-top: -1.5rem; position: relative; z-index: 10;">
        <a href="{{ route('front.certifications.chat') }}" class="btn btn-primary btn-lg shadow">
            <i class="bi bi-robot me-2" aria-hidden="true"></i> Poser une question à l'IA
        </a>
    </div>

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <form class="filter-bar search-hero" method="GET" action="{{ route('front.certifications.index') }}" role="search">
                <label class="form-label" for="numero">Numéro de certification</label>
                <div class="search-hero-row">
                    <div class="field-icon flex-grow-1">
                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                        <input class="form-control form-control-lg" id="numero" type="search" name="numero" value="{{ $numero }}"
                               placeholder="Ex. BIO-TN-2026-001" autocomplete="off" required>
                    </div>
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>Vérifier</button>
                </div>
                @if (($exemples ?? collect())->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small text-muted">
                        <span>Exemples :</span>
                        @foreach ($exemples as $exemple)
                            <a href="{{ route('front.certifications.index', ['numero' => $exemple]) }}" class="label-chip text-decoration-none">{{ $exemple }}</a>
                        @endforeach
                    </div>
                @endif
            </form>

            @if ($certification)
                @php($estValide = $certification->estValide())
                <div class="verify-result {{ $estValide ? 'is-valid' : 'is-invalid' }}">
                    <span class="verify-result-icon"><i class="bi {{ $estValide ? 'bi-patch-check-fill' : 'bi-x-octagon-fill' }}" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="h5 mb-1">{{ $estValide ? 'Certification valide' : 'Certification non valide' }}</h2>
                        <p class="mb-2">
                            Label <strong>{{ $certification->typeLabel() }}</strong> n° {{ $certification->numero }}
                            pour <strong>{{ $certification->produit?->nom }}</strong>@if ($certification->produit?->categorie) ({{ $certification->produit->categorie->nom }})@endif,
                            délivré par <strong>{{ $certification->organisme?->nom }}</strong> ({{ $certification->organisme?->pays }}, accréditation {{ $certification->organisme?->accreditation }}).
                        </p>
                        <span class="status-pill {{ $statutClasses[$certification->statutEffectif()] ?? '' }}">{{ $certification->statutLabel() }}</span>
                        <span class="small text-muted ms-2">Valable du {{ $certification->date_obtention?->format('d/m/Y') }} au {{ $certification->date_expiration?->format('d/m/Y') }}</span>
                        @if ($certification->organisme?->site_web)
                            <a href="{{ $certification->organisme->site_web }}" class="small ms-2" target="_blank" rel="noopener">Site de l'organisme <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                        @endif
                    </div>
                </div>
            @elseif ($numero !== '')
                <x-front.empty-state icon="bi-question-circle" title="Aucune certification ne porte le numéro « {{ $numero }} »" class="mt-5">
                    Vérifiez la saisie. Un numéro introuvable peut signaler un label non reconnu par NutriTrace.
                </x-front.empty-state>
            @endif

            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-5 mb-3">
                <div>
                    <span class="eyebrow mb-1">Registre</span>
                    <h2 class="h4 mb-0">Labels enregistrés</h2>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('front.certifications.index') }}" class="label-chip text-decoration-none {{ request('type') ? '' : 'fw-semibold' }}">Tous</a>
                    @foreach ($types as $valeur => $libelle)
                        <a href="{{ route('front.certifications.index', ['type' => $valeur]) }}"
                           class="label-chip text-decoration-none {{ request('type') === $valeur ? 'fw-semibold' : '' }}">{{ $libelle }}</a>
                    @endforeach
                    <span class="text-muted small ms-2">{{ $certifications->total() }} certification{{ $certifications->total() > 1 ? 's' : '' }}</span>
                </div>
            </div>

            @if ($certifications->isEmpty())
                <x-front.empty-state icon="bi-award" title="Aucun label enregistré pour l'instant">
                    Les certifications délivrées par les organismes partenaires apparaîtront ici.
                </x-front.empty-state>
            @else
                <div class="data-card">
                    <div class="table-responsive">
                        <table class="table data-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Numéro</th>
                                    <th scope="col">Label</th>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Organisme</th>
                                    <th scope="col">Expiration</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col">IA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($certifications as $item)
                                    <tr>
                                        <td class="fw-semibold"><a href="{{ route('front.certifications.index', ['numero' => $item->numero]) }}" class="text-reset">{{ $item->numero }}</a></td>
                                        <td><span class="label-chip"><i class="bi {{ $item->icone() }} me-1" aria-hidden="true"></i>{{ $item->typeLabel() }}</span></td>
                                        <td>{{ $item->produit?->nom }}</td>
                                        <td class="text-muted">{{ $item->organisme?->nom }}</td>
                                        <td class="text-muted">{{ $item->date_expiration?->format('d/m/Y') }}</td>
                                        <td><span class="status-pill {{ $statutClasses[$item->statutEffectif()] ?? '' }}">{{ $item->statutLabel() }}</span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary nt-cert-ia-btn"
                                                    data-numero="{{ $item->numero }}" data-produit="{{ $item->produit?->nom }}"
                                                    title="Avis IA immédiat sur ce label">
                                                <i class="bi bi-robot" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-5 d-flex justify-content-center">{{ $certifications->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </section>

    {{-- ── Popup IA : avis immédiat sur le label cliqué (sans quitter la page) ── --}}
    <div id="nt-cert-ia-modal" style="display:none;position:fixed;inset:0;background:rgba(15,35,25,.55);backdrop-filter:blur(3px);z-index:1080;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="nt-cert-ia-titre">
        <div style="background:#fff;border-radius:16px;max-width:520px;width:100%;max-height:88vh;overflow:auto;box-shadow:0 24px 60px rgba(0,0,0,.35);">
            <div class="d-flex align-items-start justify-content-between p-4 pb-0">
                <div>
                    <span class="eyebrow mb-1"><i class="bi bi-robot me-1" aria-hidden="true"></i> Assistant IA · Module 5</span>
                    <h3 class="h5 mb-0" id="nt-cert-ia-titre">Label</h3>
                </div>
                <button type="button" class="btn btn-sm btn-light" id="nt-cert-ia-close" aria-label="Fermer"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="p-4">
                <div id="nt-cert-ia-loading" class="text-center text-muted py-3">
                    <span class="spinner-border text-success" role="status"></span>
                    <p class="small mt-2 mb-0">L'IA vérifie ce label…</p>
                </div>
                <div id="nt-cert-ia-body" style="display:none">
                    <div id="nt-cert-ia-card" class="mb-3"></div>
                    <div id="nt-cert-ia-reponse" class="small" style="line-height:1.65"></div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="#" id="nt-cert-ia-suite" class="btn btn-sm btn-primary">
                            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i> Continuer la conversation
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="nt-cert-ia-fermer2">Fermer</button>
                    </div>
                </div>
                <div id="nt-cert-ia-erreur" class="alert alert-danger small mb-0" style="display:none"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('nt-cert-ia-modal');
                if (!modal) return;
                const titre = document.getElementById('nt-cert-ia-titre');
                const loading = document.getElementById('nt-cert-ia-loading');
                const body = document.getElementById('nt-cert-ia-body');
                const card = document.getElementById('nt-cert-ia-card');
                const reponse = document.getElementById('nt-cert-ia-reponse');
                const erreur = document.getElementById('nt-cert-ia-erreur');
                const suite = document.getElementById('nt-cert-ia-suite');
                const urlChat = '{{ route('front.certifications.chat.repondre') }}';
                const urlPageChat = '{{ route('front.certifications.chat') }}';
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || '{{ csrf_token() }}';

                function ouvrir() { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
                function fermer() { modal.style.display = 'none'; document.body.style.overflow = ''; }

                document.getElementById('nt-cert-ia-close').addEventListener('click', fermer);
                document.getElementById('nt-cert-ia-fermer2').addEventListener('click', fermer);
                modal.addEventListener('click', function (e) { if (e.target === modal) fermer(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fermer(); });

                function formater(texte) {
                    const sur = document.createElement('div');
                    sur.textContent = texte || '';
                    return sur.innerHTML.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
                }

                function afficherCarte(c) {
                    if (!c) { card.innerHTML = ''; return; }
                    const badge = c.valide
                        ? '<span class="status-pill status-valid">Valide</span>'
                        : '<span class="status-pill status-expired">' + (c.statut || 'Non valide') + '</span>';
                    const jours = (c.valide && c.jours_restants !== null && c.jours_restants !== undefined)
                        ? '<div class="nt-role-row"><span class="text-muted">Jours restants</span><strong>' + c.jours_restants + ' j</strong></div>' : '';
                    card.innerHTML = '<div class="border rounded p-3" style="background:#f4faf6">'
                        + '<div class="d-flex justify-content-between align-items-center mb-2"><strong>' + (c.type || 'Label') + ' · ' + c.numero + '</strong>' + badge + '</div>'
                        + '<div class="nt-role-row"><span class="text-muted">Produit</span><strong>' + (c.produit || '—') + '</strong></div>'
                        + '<div class="nt-role-row"><span class="text-muted">Organisme</span><strong>' + (c.organisme || '—') + '</strong></div>'
                        + '<div class="nt-role-row"><span class="text-muted">Expiration</span><strong>' + (c.date_expiration || '—') + '</strong></div>'
                        + jours + '</div>';
                }

                document.querySelectorAll('.nt-cert-ia-btn').forEach(function (btn) {
                    btn.addEventListener('click', async function () {
                        const numero = btn.dataset.numero;
                        titre.textContent = 'Label ' + numero + (btn.dataset.produit ? ' — ' + btn.dataset.produit : '');
                        suite.href = urlPageChat + '?numero=' + encodeURIComponent(numero);
                        loading.style.display = ''; body.style.display = 'none'; erreur.style.display = 'none';
                        ouvrir();
                        try {
                            const r = await fetch(urlChat, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                body: JSON.stringify({
                                    numero: numero,
                                    message: 'Le label ' + numero + ' est-il valide ? Donne-moi les détails : organisme, produit concerné, dates et statut.'
                                })
                            });
                            const d = await r.json();
                            if (!d.ok) throw new Error(d.message || 'Erreur IA.');
                            afficherCarte(d.certification);
                            reponse.innerHTML = formater(d.reponse);
                            loading.style.display = 'none'; body.style.display = '';
                        } catch (e) {
                            loading.style.display = 'none';
                            erreur.textContent = e.message || 'Erreur réseau. Réessayez.';
                            erreur.style.display = '';
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
