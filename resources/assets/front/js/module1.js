/**
 * module1.js — Module 1 Produits & Catégories (Ghada)
 * Chargé uniquement sur les pages Module 1 via @push('scripts') @vite(...).
 * Vanilla JS — aucun import Bootstrap ni jQuery.
 * Compatible Bootstrap 5 (front) et Bootstrap 4 (admin).
 */

import '../css/module1.css';

(function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════════════════════
     * Utilitaires
     * ═══════════════════════════════════════════════════════════════════════ */

    /** Token CSRF depuis meta ou input caché. */
    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;
        const input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    /** Fetch avec en-têtes Laravel. */
    function fetchJson(url, options) {
        options = options || {};
        const headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        }, options.headers || {});
        return fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers: headers }));
    }

    /** Debounce simple. */
    function debounce(fn, delai) {
        var timer;
        return function () {
            var args = arguments;
            var ctx = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delai);
        };
    }

    /** Échappe le HTML pour éviter les XSS dans les insertions dynamiques. */
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Fallback JS pour .m1-label-check (navigateurs sans :has())
     * ═══════════════════════════════════════════════════════════════════════ */

    function initLabelChecks() {
        document.querySelectorAll('.m1-label-check input[type="checkbox"]').forEach(function (cb) {
            // Synchronise l'état initial
            cb.closest('.m1-label-check').classList.toggle('is-checked', cb.checked);
            cb.addEventListener('change', function () {
                cb.closest('.m1-label-check').classList.toggle('is-checked', cb.checked);
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * A1 — Filtres dynamiques catalogue (fetch + pushState)
     * ═══════════════════════════════════════════════════════════════════════ */

    function initFiltrageAjax() {
        var form = document.getElementById('m1-form-filtres');
        var zone = document.getElementById('m1-zone-resultats');
        if (!form || !zone) return;

        var controleur = null;

        function soumettre(evt) {
            if (evt) evt.preventDefault();
            if (controleur) controleur.abort();
            controleur = new AbortController();

            var params = new URLSearchParams(new FormData(form));
            var url = form.action + '?' + params.toString();

            zone.setAttribute('aria-busy', 'true');
            zone.style.opacity = '.5';
            zone.style.pointerEvents = 'none';

            fetchJson(url, { signal: controleur.signal })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    zone.innerHTML = data.html;
                    zone.style.opacity = '1';
                    zone.style.pointerEvents = '';
                    zone.removeAttribute('aria-busy');
                    history.pushState(null, '', url);
                    // Réinitialise les boutons Comparer dans la nouvelle grille
                    initBoutonsComparer();
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') return;
                    zone.style.opacity = '1';
                    zone.style.pointerEvents = '';
                    zone.removeAttribute('aria-busy');
                    // Repli : soumission classique
                    form.submit();
                });
        }

        var soumettreDebounce   = debounce(soumettre, 350);
        var soumettreTexte      = debounce(soumettre, 600);

        form.addEventListener('submit', soumettre);

        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', soumettreDebounce);
        });

        form.querySelectorAll('input[type="checkbox"]').forEach(function (el) {
            el.addEventListener('change', soumettreDebounce);
        });

        form.querySelectorAll('input[type="search"], input[type="text"]').forEach(function (el) {
            el.addEventListener('input', soumettreTexte);
        });

        // Navigation navigateur
        window.addEventListener('popstate', function () { window.location.reload(); });
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * A2 — Boutons Comparer (AJAX ajouter / retirer)
     * ═══════════════════════════════════════════════════════════════════════ */

    function initBoutonsComparer() {
        document.querySelectorAll('.m1-form-comparer[data-ajax="true"]').forEach(function (form) {
            if (form.dataset.initAjax) return;
            form.dataset.initAjax = '1';

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                if (btn) btn.disabled = true;

                var formData = new FormData(form);
                var method = (form.querySelector('[name="_method"]') || {}).value || 'POST';

                fetchJson(form.action, { method: method === 'DELETE' ? 'POST' : method, body: formData })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                    .then(function (res) {
                        if (!res.ok) {
                            afficherFlash(res.data.error || 'Une erreur est survenue.', 'danger');
                            if (btn) btn.disabled = false;
                            return;
                        }
                        mettreAJourBarreComparaison(res.data.nb, res.data.max);

                        var carte = form.closest('.m1-carte-produit');
                        if (carte) {
                            carte.classList.toggle('m1-carte-produit--selectionnee', !!res.data.selectionne);
                        }

                        // Mise à jour visuelle minimale du bouton
                        if (btn) {
                            btn.disabled = false;
                            if (res.data.selectionne) {
                                btn.classList.remove('btn-outline-primary');
                                btn.classList.add('btn-success');
                                btn.innerHTML = '<i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Dans la comparaison';
                                var mInput = form.querySelector('[name="_method"]');
                                if (mInput) mInput.value = 'DELETE';
                            } else {
                                btn.classList.add('btn-outline-primary');
                                btn.classList.remove('btn-success');
                                btn.innerHTML = '<i class="bi bi-bar-chart-steps me-1" aria-hidden="true"></i>Comparer';
                                var mInput2 = form.querySelector('[name="_method"]');
                                if (mInput2) mInput2.value = 'POST';
                            }
                        }
                    })
                    .catch(function () {
                        if (btn) btn.disabled = false;
                        afficherFlash('Erreur réseau. Veuillez réessayer.', 'danger');
                    });
            });
        });
    }

    function mettreAJourBarreComparaison(nb, max) {
        var barre   = document.getElementById('m1-barre-comparaison');
        var nbEl    = document.getElementById('m1-barre-nb');
        var btnVoir = document.getElementById('m1-btn-voir-comparaison');
        if (!barre) return;

        barre.dataset.nb = nb;
        if (nbEl) nbEl.textContent = nb;
        barre.classList.toggle('m1-barre-comparaison--visible', nb > 0);

        if (btnVoir) {
            btnVoir.classList.toggle('disabled', nb < 2);
            btnVoir.setAttribute('aria-disabled', nb < 2 ? 'true' : 'false');
        }
    }

    function initViderComparaison() {
        var form = document.querySelector('.m1-form-vider');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchJson(form.action, { method: 'POST', body: new FormData(form) })
                .then(function (r) {
                    if (!r.ok) return;
                    var max = parseInt((document.getElementById('m1-barre-comparaison') || {}).dataset && document.getElementById('m1-barre-comparaison').dataset.max || '2');
                    mettreAJourBarreComparaison(0, max);
                    document.querySelectorAll('.m1-carte-produit--selectionnee').forEach(function (c) {
                        c.classList.remove('m1-carte-produit--selectionnee');
                    });
                })
                .catch(function () {});
        });
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * A3 — Onglets lots (fiche produit, fetch HTML)
     * ═══════════════════════════════════════════════════════════════════════ */

    function initOnglets() {
        var conteneur = document.getElementById('m1-onglets-lots');
        var detail    = document.getElementById('m1-lot-detail');
        if (!conteneur || !detail) return;

        conteneur.addEventListener('click', function (e) {
            var btn = e.target.closest('.m1-onglet-lot');
            if (!btn || btn.classList.contains('m1-onglet-lot--actif')) return;
            e.preventDefault();

            conteneur.querySelectorAll('.m1-onglet-lot').forEach(function (b) {
                b.classList.remove('m1-onglet-lot--actif');
            });
            btn.classList.add('m1-onglet-lot--actif');

            var url = btn.dataset.url;
            if (!url) return;

            detail.innerHTML = '<div class="m1-lot-detail-loader"><span class="m1-spinner" aria-hidden="true"></span> Chargement…</div>';

            fetchJson(url)
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (data) { detail.innerHTML = data.html; })
                .catch(function () {
                    detail.innerHTML = '<p class="small text-danger">Impossible de charger ce lot.</p>';
                });
        });
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * IA3 — Recommandations (IntersectionObserver, chargement différé)
     * ═══════════════════════════════════════════════════════════════════════ */

    function initRecommandations() {
        var zone = document.getElementById('m1-zone-recommandations');
        if (!zone) return;
        var url = zone.dataset.url;
        if (!url) return;

        var charge = false;

        var observer = new IntersectionObserver(function (entries) {
            if (charge || !entries[0].isIntersecting) return;
            charge = true;
            observer.disconnect();

            zone.innerHTML = '<div class="m1-lot-detail-loader"><span class="m1-spinner" aria-hidden="true"></span> Chargement des recommandations…</div>';

            fetchJson(url)
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (data) { zone.innerHTML = data.html || ''; })
                .catch(function () {
                    zone.innerHTML = '<p class="text-muted small">Recommandations indisponibles pour le moment.</p>';
                });
        }, { rootMargin: '0px 0px 200px 0px' });

        observer.observe(zone);
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * IA1 — Assistant catégorisation
     * ═══════════════════════════════════════════════════════════════════════ */

    function initAssistantCategorisation() {
        var btn = document.getElementById('m1-ia-btn-categoriser');
        if (!btn) return;
        var url = btn.dataset.url;

        btn.addEventListener('click', function () {
            var nomInput  = document.getElementById('nom') || document.querySelector('[name="nom"]');
            var descInput = document.getElementById('description') || document.querySelector('[name="description"]');
            var resultat  = document.getElementById('m1-ia-resultat-cat');

            if (!nomInput || !nomInput.value.trim()) {
                afficherFlash('Entrez d\'abord le nom du produit.', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="m1-spinner" aria-hidden="true"></span> Analyse…';

            var corps = new FormData();
            corps.append('_token', csrfToken());
            corps.append('nom', nomInput.value.trim());
            if (descInput) corps.append('description', descInput.value.trim());

            fetchJson(url, { method: 'POST', body: corps })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    if (!res.ok) {
                        afficherFlash(res.data.message || 'Erreur IA.', 'danger');
                        return;
                    }
                    afficherResultatCategorisation(res.data, resultat);
                })
                .catch(function () { afficherFlash('Erreur réseau.', 'danger'); })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-robot me-1" aria-hidden="true"></i>Suggérer catégorie';
                });
        });
    }

    function afficherResultatCategorisation(data, conteneur) {
        if (!conteneur) return;
        conteneur.classList.add('m1-ia-resultat--visible');

        var lignes = [
            { label: 'Catégorie', champ: 'categorie_id', valeur: data.categorie_nom || data.categorie_id, appliquer: data.categorie_id },
            { label: 'Composition', champ: 'composition',  valeur: data.composition,  appliquer: data.composition },
            { label: 'Description', champ: 'description',  valeur: data.description,  appliquer: data.description },
            { label: 'Confiance',   champ: null,            valeur: data.confiance ? Math.round(data.confiance * 100) + ' %' : null, appliquer: null },
            { label: 'Justification', champ: null,          valeur: data.justification, appliquer: null },
        ];

        conteneur.innerHTML = lignes.filter(function (l) { return l.valeur; }).map(function (l) {
            return '<div class="m1-ia-champ-ligne">' +
                '<span class="m1-ia-champ-ligne__label">' + esc(l.label) + '</span>' +
                '<span class="m1-ia-champ-ligne__valeur">' + esc(String(l.valeur)) + '</span>' +
                (l.champ && l.appliquer !== null && l.appliquer !== undefined
                    ? '<button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 m1-ia-appliquer" data-champ="' + esc(l.champ) + '" data-valeur="' + esc(String(l.appliquer)) + '">Appliquer</button>'
                    : '') +
                '</div>';
        }).join('');

        if (data.avertissement) {
            conteneur.innerHTML += '<p class="small text-warning mt-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>' + esc(data.avertissement) + '</p>';
        }

        conteneur.querySelectorAll('.m1-ia-appliquer').forEach(function (b) {
            b.addEventListener('click', function () {
                var input = document.getElementById(b.dataset.champ) || document.querySelector('[name="' + b.dataset.champ + '"]');
                if (input) {
                    input.value = b.dataset.valeur;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    b.textContent = '✓';
                    b.classList.replace('btn-outline-primary', 'btn-success');
                    b.disabled = true;
                }
            });
        });

        // Bouton « Tout appliquer »
        var zoneTout = document.getElementById('m1-ia-tout-appliquer');
        var btnTout  = document.getElementById('m1-ia-btn-tout-appliquer');
        if (zoneTout && btnTout) {
            zoneTout.style.display = '';
            btnTout.onclick = function () {
                conteneur.querySelectorAll('.m1-ia-appliquer:not(:disabled)').forEach(function (b) { b.click(); });
                zoneTout.style.display = 'none';
            };
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * IA2 — OCR étiquette
     * ═══════════════════════════════════════════════════════════════════════ */

    function initAssistantOcr() {
        var btn   = document.getElementById('m1-ia-btn-ocr');
        var input = document.getElementById('m1-ia-fichier-ocr');
        if (!btn || !input) return;
        var url = btn.dataset.url;

        btn.addEventListener('click', function () { input.click(); });

        input.addEventListener('change', function () {
            var fichier = input.files[0];
            if (!fichier) return;

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(fichier.type)) {
                afficherFlash('Format non supporté. Utilisez JPG, PNG ou WebP.', 'warning');
                return;
            }
            if (fichier.size > 4 * 1024 * 1024) {
                afficherFlash('Fichier trop volumineux (4 Mo max).', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="m1-spinner" aria-hidden="true"></span> Lecture…';

            var corps = new FormData();
            corps.append('_token', csrfToken());
            corps.append('image', fichier);

            fetchJson(url, { method: 'POST', body: corps })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    if (!res.ok) {
                        afficherFlash(res.data.message || 'Erreur OCR.', 'danger');
                        return;
                    }
                    var cibles = {
                        nom:         document.getElementById('nom')         || document.querySelector('[name="nom"]'),
                        code_barres: document.getElementById('code_barres') || document.querySelector('[name="code_barres"]'),
                        origine:     document.getElementById('origine')     || document.querySelector('[name="origine"]'),
                        composition: document.getElementById('composition') || document.querySelector('[name="composition"]'),
                    };
                    Object.keys(cibles).forEach(function (cle) {
                        if (cibles[cle] && res.data[cle]) {
                            cibles[cle].value = res.data[cle];
                            cibles[cle].dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                    afficherFlash('Étiquette lue. Vérifiez les champs avant d\'enregistrer.', 'success');
                })
                .catch(function () { afficherFlash('Erreur réseau.', 'danger'); })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-camera me-1" aria-hidden="true"></i>Lire étiquette';
                    input.value = '';
                });
        });
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Flash message (compatible BS4 et BS5)
     * ═══════════════════════════════════════════════════════════════════════ */

    function afficherFlash(message, type) {
        // Cherche la zone flash du template, sinon insère avant le premier <section>
        var zone = document.querySelector('.flash-container') ||
                   document.querySelector('main > .container') ||
                   document.querySelector('main');
        if (!zone) return;

        var div = document.createElement('div');
        // BS5 : alert-dismissible avec btn-close | BS4 : × classique
        div.className = 'alert alert-' + type + ' alert-dismissible fade show m1-flash mt-3';
        div.setAttribute('role', 'alert');
        div.innerHTML = esc(message) +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer">' +
            '</button>';

        zone.prepend(div);
        setTimeout(function () { if (div.parentNode) div.parentNode.removeChild(div); }, 5000);
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Initialisation globale
     * ═══════════════════════════════════════════════════════════════════════ */

    function init() {
        initLabelChecks();
        initFiltrageAjax();
        initBoutonsComparer();
        initViderComparaison();
        initOnglets();
        initRecommandations();
        initAssistantCategorisation();
        initAssistantOcr();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
