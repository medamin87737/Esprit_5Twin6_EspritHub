@extends('layouts.front')

@section('title', 'Qualité')

@section('content')
    @php
        $risque = session('risque_ia');
        $analyseResumeeId = session('analyse_resumee_id');
        $resumeAffiche = session('resume_ia_affiche');
        $nomProduit = $lot?->produit?->nom ?? 'ce produit';
    @endphp

    <x-front.page-header
        eyebrow="Mon espace"
        title="Qualité"
        image="consommateur-bowl.webp"
        subtitle="Analyses laboratoire, statut du lot et orientation selon votre profil."
    />

    <section class="page-section pt-0 nt-qualite-page">
        <div class="container px-4 px-lg-5">
            <div class="nt-q-layout">
                <div class="nt-q-layout__main">
                    @if ($introuvable)
                        <div class="nt-q-empty">
                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                            <h2 class="h5 mb-2">Lot introuvable</h2>
                            <p class="mb-0">Aucun lot ne correspond à « {{ $numero }} ». Vérifiez le numéro sur l’emballage.</p>
                        </div>
                    @elseif ($lot)
                        @include('pages.front.consommateur._qualite-lot', ['lot' => $lot, 'synthese' => $synthese])
                    @else
                        <div class="nt-q-empty nt-q-empty--welcome">
                            <div class="nt-q-empty__steps" aria-hidden="true">
                                <span><span class="nt-q-empty__n">1</span> Lot</span>
                                <span><span class="nt-q-empty__n">2</span> Analyses</span>
                                <span><span class="nt-q-empty__n">3</span> Profil</span>
                            </div>
                            <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                            <h2 class="h5 mb-2">Consulter la qualité d’un lot</h2>
                            <p class="mb-0">Saisissez un numéro à droite pour afficher les analyses et l’évaluation profil.</p>
                        </div>
                    @endif
                </div>

                <aside class="nt-q-layout__rail" aria-label="Recherche lot">
                    <form method="GET" action="{{ route('consommateur.qualite.index') }}" class="nt-q-mini nt-q-mini--search" role="search">
                        <div class="nt-q-mini__icon" aria-hidden="true"><i class="bi bi-upc-scan"></i></div>
                        <h2 class="nt-q-mini__title">Trouver un lot</h2>
                        <label class="nt-q-mini__label" for="numero">Numéro de lot</label>
                        <input
                            type="text"
                            id="numero"
                            name="numero"
                            class="nt-q-mini__input"
                            value="{{ $numero }}"
                            required
                            placeholder="LOT-2026-0001"
                            autocomplete="off"
                        >
                        <button type="submit" class="nt-q-btn nt-q-btn--primary w-100 mt-2">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            Consulter
                        </button>
                    </form>
                </aside>
            </div>

            {{-- Rangée de cartes alignées : analyses + score + assistant --}}
            @if ($lot)
                <div class="nt-q-cards-row" aria-label="Actions qualité">
                    @forelse ($lot->analyses as $analyse)
                        @include('pages.front.consommateur._qualite-analyse', [
                            'analyse' => $analyse,
                            'analyseResumeeId' => $analyseResumeeId,
                            'resumeAffiche' => $resumeAffiche,
                        ])
                    @empty
                        <div class="nt-q-mini nt-q-mini--wait">
                            <div class="nt-q-mini__icon" aria-hidden="true"><i class="bi bi-clipboard2"></i></div>
                            <h2 class="nt-q-mini__title">Analyses</h2>
                            <p class="nt-q-mini__text">Aucune analyse déclarée pour ce lot.</p>
                        </div>
                    @endforelse

                    <div class="nt-q-mini nt-q-mini--profil">
                        <div class="nt-q-mini__icon" aria-hidden="true"><i class="bi bi-robot"></i></div>
                        <h2 class="nt-q-mini__title">Score et conseil profil</h2>
                        <p class="nt-q-mini__text">Questionnaire court, puis score et conseils adaptés à votre profil.</p>
                        @if (is_array($risque))
                            <p class="nt-q-mini__meta">
                                {{ $risque['libelle'] }} · {{ $risque['score'] }}/100
                                <button type="button" class="nt-link-btn" id="nt-risk-reopen">Revoir</button>
                            </p>
                        @endif
                        <button
                            type="button"
                            class="nt-q-btn nt-q-btn--primary w-100"
                            data-nt-open-risk-q
                            data-action="{{ route('consommateur.qualite.risque', $lot) }}"
                            data-csrf="{{ csrf_token() }}"
                            data-produit="{{ $nomProduit }}"
                        >
                            <i class="bi bi-robot" aria-hidden="true"></i>
                            Évaluer IA
                        </button>
                        <a href="{{ route('front.lots.show', $lot) }}" class="nt-q-mini__link mt-2 d-inline-flex">
                            Parcours du lot <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                        <p class="nt-q-disclaimer">Pas un avis médical.</p>
                    </div>

                    <div class="nt-q-mini nt-q-mini--soft">
                        <div class="nt-q-mini__icon" aria-hidden="true"><i class="bi bi-chat-dots"></i></div>
                        <h2 class="nt-q-mini__title">Assistant</h2>
                        <p class="nt-q-mini__text mb-2">Discutez du lot, des analyses ou de votre profil avec l’assistant qualité.</p>
                        @can('espace-consommateur')
                            <button
                                type="button"
                                class="nt-chat__fab nt-chat__fab--batterie nt-q-mini__chat"
                                data-nt-chat-open
                                aria-expanded="false"
                                aria-controls="nt-chat-panel"
                                title="Assistant qualité IA"
                            >
                                <span class="nt-qualite-avatar__pulse" aria-hidden="true"></span>
                                <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                                <span class="nt-qualite-avatar__badge">Qualité</span>
                                <span class="visually-hidden">Ouvrir l'assistant qualité IA</span>
                            </button>
                        @endcan
                    </div>
                </div>
            @else
                <div class="nt-q-cards-row nt-q-cards-row--solo">
                    <div class="nt-q-mini nt-q-mini--soft">
                        <div class="nt-q-mini__icon" aria-hidden="true"><i class="bi bi-chat-dots"></i></div>
                        <h2 class="nt-q-mini__title">Assistant</h2>
                        <p class="nt-q-mini__text mb-2">Ouvrez le chat Qualité ou consultez d’abord un lot pour un contexte précis.</p>
                        @can('espace-consommateur')
                            <button
                                type="button"
                                class="nt-chat__fab nt-chat__fab--batterie nt-q-mini__chat"
                                data-nt-chat-open
                                aria-expanded="false"
                                aria-controls="nt-chat-panel"
                                title="Assistant qualité IA"
                            >
                                <span class="nt-qualite-avatar__pulse" aria-hidden="true"></span>
                                <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                                <span class="nt-qualite-avatar__badge">Qualité</span>
                                <span class="visually-hidden">Ouvrir l'assistant qualité IA</span>
                            </button>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    @vite([
        'resources/assets/admin/js/analyse-ia.js',
        'resources/assets/front/js/qualite-risque.js',
    ])
    <script>
        (function () {
            function openResume(texte, titre) {
                if (!window.ntIaShowResume) return;
                window.ntIaShowResume(texte || '', titre || 'Synthèse qualité');
            }

            function bindResumeButtons() {
                document.querySelectorAll('[data-nt-open-resume]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        openResume(btn.getAttribute('data-texte'), btn.getAttribute('data-titre'));
                    });
                });
            }

            function autoResume() {
                var el = document.querySelector('[data-nt-resume-popup][data-auto-open="1"]')
                    || document.querySelector('[data-nt-resume-popup]');
                @if (session('resume_ia_affiche'))
                if (el) openResume(el.getAttribute('data-texte'), el.getAttribute('data-titre'));
                @endif
            }

            function onReady() {
                bindResumeButtons();
                autoResume();
            }

            if (window.ntIaShowResume) onReady();
            else document.addEventListener('nt-ia-ready', onReady, { once: true });

            document.getElementById('nt-risk-reopen')?.addEventListener('click', function () {
                var payload = document.getElementById('nt-risk-result-data');
                if (!payload || !window.ntShowRisqueResult) return;
                try {
                    window.ntShowRisqueResult(JSON.parse(payload.textContent || '{}'));
                } catch (e) {}
            });
        })();
    </script>
@endpush
