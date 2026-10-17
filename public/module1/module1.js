/**
 * module1.js — Module 1 Produits & Catégories (Ghada)
 * Vanilla JS, aucune dépendance externe.
 * Chargé uniquement sur les pages Module 1 via @push('scripts').
 *
 * Fonctionnalités A1 (catalogue enrichi) :
 *  - Filtres dynamiques : fetch + remplacement zone résultats + history.pushState
 *  - Soumission auto sur changement des selects/checkboxes
 *
 * Fonctionnalités A2 (comparateur) :
 *  - Boutons Comparer : AJAX ajouter/retirer sans rechargement
 *  - Barre flottante mise à jour en temps réel
 *
 * Fonctionnalités A3 (fiche produit) :
 *  - Onglets lots : fetch JSON → rendu HTML du détail
 *
 * Fonctionnalités IA3 (recommandations) :
 *  - IntersectionObserver : chargement différé du bloc recommandations
 *
 * Fonctionnalités IA1/IA2 (assistant IA) :
 *  - Boutons "Suggérer catégorie", "Lire étiquette" → fetch → boutons Appliquer
 */

(function () {
    'use strict';

    /* ════════════════════════════════════════════════════════════════════════
     * Utilitaires
     * ════════════════════════════════════════════════════════════════════════ */

    /** Récupère le token CSRF depuis la balise meta ou un input caché. */
    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;
        const input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    /** Effectue un fetch JSON avec les en-têtes Laravel attendus. */
    function fetchJson(url, options = {}) {
        const headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        }, options.headers || {});

        return fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers }));
    }

    /** Annule un timer existant et en crée un nouveau (debounce simple). */
    function debounce(fn, delai) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delai);
        };
    }

    /* ════════════════════════════════════════════════════════════════════════
     * A1 — Filtres dynamiques du catalogue
     * ════════════════════════════════════════════════════════════════════════ */

    function initFiltrageAjax() {
        const form  = document.getElementById('m1-form-filtres');
        const zone  = document.getElementById('m1-zone-resultats');
        if (!form || !zone) return;

        let controleur = null; // AbortController courant

        /** Soumet le formulaire en AJAX et remplace la zone de résultats. */
        async function soumettre(event) {
            if (event) event.preventDefault();

            // Annule la requête précédente si elle est encore en cours
            if (controleur) controleur.abort();
            controleur = new AbortController();

            const params = new URLSearchParams(new FormData(form));
            params.delete('_fragment'); // nettoyage
            const url = form.action + '?' + params.toString();

            // Indicateur de chargement
            zone.setAttribute('aria-busy', 'true');
            zone.style.opacity = '.5';

            try {
                const reponse = await fetchJson(url, {
                    signal: controleur.signal,
                    headers: { 'Accept': 'application/json' },
                });

                if (!reponse.ok) throw new Error('Erreur réseau ' + reponse.status);

                const data = await reponse.json();
                zone.innerHTML = data.html;
                zone.style.opacity = '1';
                zone.removeAttribute('aria-busy');

                // Met à jour l'URL sans rechargement
                history.pushState(null, '', url);

                // Ré-initialise les boutons Comparer dans la nouvelle grille
                initBoutonsComparer();

            } catch (err) {
                if (err.name === 'AbortError') return; // requête annulée volontairement
                zone.style.opacity = '1';
                zone.removeAttribute('aria-busy');
                // En cas d'erreur réseau, on laisse le formulaire se soumettre normalement
                form.submit();
            }
        }

        // Soumission du formulaire
        form.addEventListener('submit', soumettre);

        // Soumission auto sur changement des selects et checkboxes (avec debounce)
        const soumettreDebounce = debounce(soumettre, 350);
        form.querySelectorAll('select').forEach(el => {
            el.addEventListener('change', soumettreDebounce);
        });
        form.querySelectorAll('input[type="checkbox"]').forEach(el => {
            el.addEventListener('change', soumettreDebounce);
        });

        // Soumission auto sur saisie dans les champs texte (debounce 600ms)
        const soumettreTexte = debounce(soumettre, 600);
        form.querySelectorAll('input[type="search"], input[type="text"]').forEach(el => {
            el.addEventListener('input', soumettreTexte);
        });

        // Navigation arrière/avant du navigateur : recharge la page
        window.addEventListener('popstate', () => window.location.reload());
    }

    /* ════════════════════════════════════════════════════════════════════════
     * A2 — Boutons Comparer (AJAX ajouter/retirer)
     * ════════════════════════════════════════════════════════════════════════ */

    function initBoutonsComparer() {
        document.querySelectorAll('.m1-form-comparer[data-ajax="true"]').forEach(function (form) {
            if (form.dataset.initAjax) return; // déjà initialisé
            form.dataset.initAjax = '1';

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const btn = form.querySelector('button[type="submit"]');
                if (btn) btn.disabled = true;

                const method = (form.querySelector('[name="_method"]')?.value || 'POST').toUpperCase();
                const url    = form.action;
                const body   = new FormData(form);

                try {
                    const reponse = await fetchJson(url, {
                        method: method === 'DELETE' ? 'POST' : method,
                        body: body,
                    });

                    const data = await reponse.json();

                    if (!reponse.ok) {
                        afficherFlash(data.error || 'Une erreur est survenue.', 'danger');
                        if (btn) btn.disabled = false;
                        return;
                    }

                    // Met à jour la barre flottante
                    mettreAJourBarreComparaison(data.nb, data.max);

                    // Recharge la carte (ou juste le bouton) pour refléter le nouvel état
                    // On recharge la page silencieusement pour simplifier (pushState déjà en place)
                    // Alternativement on pourrait juste mettre à jour la carte visiblement :
                    const carte = form.closest('.m1-carte-produit');
                    if (carte) {
                        carte.classList.toggle('m1-carte-produit--selectionnee', !!data.selectionne);
                    }

                    // Rechargement du fragment de la carte si fourni
                    if (data.html_carte) {
                        const conteneur = form.closest('[class*="col-"]') || form.closest('.m1-carte-produit');
                        if (conteneur) conteneur.outerHTML = data.html_carte;
                        initBoutonsComparer(); // ré-init sur le nouveau DOM
                    } else {
                        // Mise à jour visuelle minimale du bouton
                        if (btn) {
                            btn.disabled = false;
                            if (data.selectionne) {
                                btn.classList.remove('btn-outline-primary');
                                btn.classList.add('btn-success', 'm1-btn-comparer--retirer');
                                btn.innerHTML = '<i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Dans la comparaison';
                                // Changer l'action du formulaire en DELETE
                                form.querySelector('[name="_method"]')?.setAttribute('value', 'DELETE');
                            } else {
                                btn.classList.add('btn-outline-primary');
                                btn.classList.remove('btn-success', 'm1-btn-comparer--retirer');
                                btn.innerHTML = '<i class="bi bi-bar-chart-steps me-1" aria-hidden="true"></i>Comparer';
                                form.querySelector('[name="_method"]')?.setAttribute('value', 'POST');
                            }
                        }
                    }

                } catch (err) {
                    if (btn) btn.disabled = false;
                    afficherFlash('Erreur réseau. Veuillez réessayer.', 'danger');
                }
            });
        });
    }

    /** Met à jour la barre flottante de comparaison. */
    function mettreAJourBarreComparaison(nb, max) {
        const barre   = document.getElementById('m1-barre-comparaison');
        const nbEl    = document.getElementById('m1-barre-nb');
        const btnVoir = document.getElementById('m1-btn-voir-comparaison');
        if (!barre) return;

        barre.dataset.nb = nb;
        if (nbEl) nbEl.textContent = nb;

        barre.classList.toggle('m1-barre-comparaison--visible', nb > 0);

        if (btnVoir) {
            btnVoir.classList.toggle('disabled', nb < 2);
            btnVoir.setAttribute('aria-disabled', nb < 2 ? 'true' : 'false');
        }
    }

    /** Initialise le bouton Vider la sélection en AJAX. */
    function initViderComparaison() {
        const form = document.querySelector('.m1-form-vider');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            try {
                const reponse = await fetchJson(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                });
                if (reponse.ok) {
                    mettreAJourBarreComparaison(0, parseInt(
                        document.getElementById('m1-barre-comparaison')?.dataset.max || '2'
                    ));
                    // Retire la classe sélectionnée de toutes les cartes
                    document.querySelectorAll('.m1-carte-produit--selectionnee').forEach(c => {
                        c.classList.remove('m1-carte-produit--selectionnee');
                    });
                }
            } catch (err) { /* silencieux */ }
        });
    }

    /* ════════════════════════════════════════════════════════════════════════
     * A3 — Onglets lots (fiche produit)
     * ════════════════════════════════════════════════════════════════════════ */

    function initOnglets() {
        const conteneur = document.getElementById('m1-onglets-lots');
        const detail    = document.getElementById('m1-lot-detail');
        if (!conteneur || !detail) return;

        conteneur.addEventListener('click', async function (e) {
            const btn = e.target.closest('.m1-onglet-lot');
            if (!btn || btn.classList.contains('m1-onglet-lot--actif')) return;

            e.preventDefault();

            // Marqueur actif
            conteneur.querySelectorAll('.m1-onglet-lot').forEach(b => b.classList.remove('m1-onglet-lot--actif'));
            btn.classList.add('m1-onglet-lot--actif');

            const url = btn.dataset.url;
            if (!url) return;

            detail.innerHTML = '<div class="m1-lot-detail-loader"><span class="m1-spinner"></span> Chargement…</div>';

            try {
                const reponse = await fetchJson(url, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!reponse.ok) throw new Error('Erreur ' + reponse.status);

                const data = await reponse.json();
                detail.innerHTML = data.html;

            } catch (err) {
                detail.innerHTML = '<p class="text-danger small">Impossible de charger ce lot.</p>';
            }
        });
    }

    /* ════════════════════════════════════════════════════════════════════════
     * IA3 — Recommandations (IntersectionObserver)
     * ════════════════════════════════════════════════════════════════════════ */

    function initRecommandations() {
        const zone = document.getElementById('m1-zone-recommandations');
        if (!zone) return;

        const url = zone.dataset.url;
        if (!url) return;

        let charge = false;

        const observer = new IntersectionObserver(async function (entries) {
            if (charge || !entries[0].isIntersecting) return;
            charge = true;
            observer.disconnect();

            zone.innerHTML = '<div class="m1-lot-detail-loader"><span class="m1-spinner"></span> Chargement des recommandations…</div>';

            try {
                const reponse = await fetchJson(url, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await reponse.json();
                zone.innerHTML = data.html || '';
            } catch (err) {
                zone.innerHTML = '<p class="text-muted small">Recommandations indisponibles pour le moment.</p>';
            }
        }, { rootMargin: '0px 0px 200px 0px' });

        observer.observe(zone);
    }

    /* ════════════════════════════════════════════════════════════════════════
     * IA1 — Assistant catégorisation
     * ════════════════════════════════════════════════════════════════════════ */

    function initAssistantCategorisation() {
        const btn = document.getElementById('m1-ia-btn-categoriser');
        if (!btn) return;

        const url = btn.dataset.url;

        btn.addEventListener('click', async function () {
            const nomInput   = document.getElementById('m1-ia-nom') || document.getElementById('nom') || document.querySelector('[name="nom"]');
            const descInput  = document.getElementById('m1-ia-desc') || document.getElementById('description') || document.querySelector('[name="description"]');
            const resultat   = document.getElementById('m1-ia-resultat-cat');

            if (!nomInput || !nomInput.value.trim()) {
                afficherFlash('Entrez d\'abord le nom du produit.', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="m1-spinner"></span> Analyse…';

            try {
                const corps = new FormData();
                corps.append('_token', csrfToken());
                corps.append('nom', nomInput.value.trim());
                if (descInput) corps.append('description', descInput.value.trim());

                const reponse = await fetchJson(url, { method: 'POST', body: corps });
                const data    = await reponse.json();

                if (!reponse.ok) {
                    afficherFlash(data.message || 'Erreur IA.', 'danger');
                    return;
                }

                afficherResultatCategorisation(data, resultat);

            } catch (err) {
                afficherFlash('Erreur réseau.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-robot me-1" aria-hidden="true"></i>Suggérer catégorie';
            }
        });
    }

    function afficherResultatCategorisation(data, conteneur) {
        if (!conteneur) return;
        conteneur.classList.add('m1-assistant-ia__resultat--visible');

        const lignes = [
            { cle: 'categorie_id',  label: 'Catégorie',    champ: 'categorie_id',  valeur: data.categorie_nom || data.categorie_id },
            { cle: 'type',          label: 'Type',          champ: null,            valeur: data.type },
            { cle: 'composition',   label: 'Composition',   champ: 'composition',   valeur: data.composition },
            { cle: 'description',   label: 'Description',   champ: 'description',   valeur: data.description },
            { cle: 'confiance',     label: 'Confiance',     champ: null,            valeur: data.confiance ? Math.round(data.confiance * 100) + ' %' : null },
            { cle: 'justification', label: 'Justification', champ: null,            valeur: data.justification },
        ];

        conteneur.innerHTML = lignes.filter(l => l.valeur).map(l => `
            <div class="m1-ia-champ-ligne">
                <span class="m1-ia-champ-ligne__label">${l.label}</span>
                <span class="m1-ia-champ-ligne__valeur">${escHtml(String(l.valeur))}</span>
                ${l.champ ? `<button type="button" class="btn btn-outline-primary btn-sm btn-appliquer"
                    data-champ="${l.champ}" data-valeur="${escAttr(String(l.cle === 'categorie_id' ? data.categorie_id : l.valeur))}">
                    Appliquer</button>` : ''}
            </div>
        `).join('');

        // Boutons Appliquer
        conteneur.querySelectorAll('.btn-appliquer').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const champ  = btn.dataset.champ;
                const valeur = btn.dataset.valeur;
                const input  = document.getElementById(champ) || document.querySelector('[name="' + champ + '"]');
                if (input) {
                    input.value = valeur;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    btn.textContent = '✓';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-success');
                    btn.disabled = true;
                }
            });
        });
    }

    /* ════════════════════════════════════════════════════════════════════════
     * IA2 — OCR étiquette
     * ════════════════════════════════════════════════════════════════════════ */

    function initAssistantOcr() {
        const btn     = document.getElementById('m1-ia-btn-ocr');
        const input   = document.getElementById('m1-ia-fichier-ocr');
        if (!btn || !input) return;

        const url = btn.dataset.url;

        btn.addEventListener('click', function () {
            input.click();
        });

        input.addEventListener('change', async function () {
            const fichier = input.files[0];
            if (!fichier) return;

            const typesAcceptes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!typesAcceptes.includes(fichier.type)) {
                afficherFlash('Format non supporté. Utilisez JPG, PNG ou WebP.', 'warning');
                return;
            }
            if (fichier.size > 4 * 1024 * 1024) {
                afficherFlash('Fichier trop volumineux (4 Mo max).', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="m1-spinner"></span> Lecture…';

            const corps = new FormData();
            corps.append('_token', csrfToken());
            corps.append('image', fichier);

            try {
                const reponse = await fetchJson(url, { method: 'POST', body: corps });
                const data    = await reponse.json();

                if (!reponse.ok) {
                    afficherFlash(data.message || 'Erreur OCR.', 'danger');
                    return;
                }

                // Applique les champs reconnus
                const champsCibles = {
                    nom: document.getElementById('nom') || document.querySelector('[name="nom"]'),
                    code_barres: document.getElementById('code_barres') || document.querySelector('[name="code_barres"]'),
                    origine: document.getElementById('origine') || document.querySelector('[name="origine"]'),
                    composition: document.getElementById('composition') || document.querySelector('[name="composition"]'),
                };

                Object.entries(champsCibles).forEach(([cle, el]) => {
                    if (el && data[cle]) {
                        el.value = data[cle];
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                afficherFlash('Étiquette lue avec succès. Vérifiez les champs avant d\'enregistrer.', 'success');

            } catch (err) {
                afficherFlash('Erreur réseau.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-camera me-1" aria-hidden="true"></i>Lire étiquette';
                input.value = ''; // permet de re-sélectionner le même fichier
            }
        });
    }

    /* ════════════════════════════════════════════════════════════════════════
     * Flash message
     * ════════════════════════════════════════════════════════════════════════ */

    function afficherFlash(message, type) {
        const zone = document.querySelector('.alert-flash-zone') || document.querySelector('main');
        if (!zone) return;

        const div = document.createElement('div');
        div.className = `alert alert-${type} alert-dismissible fade show m1-flash`;
        div.setAttribute('role', 'alert');
        div.innerHTML = `${escHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>`;

        zone.prepend(div);
        setTimeout(() => div.remove(), 5000);
    }

    /* ════════════════════════════════════════════════════════════════════════
     * Helpers XSS
     * ════════════════════════════════════════════════════════════════════════ */

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function escAttr(str) {
        return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ════════════════════════════════════════════════════════════════════════
     * Initialisation
     * ════════════════════════════════════════════════════════════════════════ */

    document.addEventListener('DOMContentLoaded', function () {
        initFiltrageAjax();
        initBoutonsComparer();
        initViderComparaison();
        initOnglets();
        initRecommandations();
        initAssistantCategorisation();
        initAssistantOcr();
    });

})();
