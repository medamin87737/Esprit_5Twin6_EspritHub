{{-- Modale Qualité partagée : ouverte par les badges « Qualité » (cartes + fiche produit).
     Le contenu est chargé en AJAX depuis data-url du bouton cliqué. --}}
<div class="modal fade nt-qualite-modal" id="ntQualiteModal" tabindex="-1" aria-labelledby="ntQualiteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content nt-qualite-modal__content">
            <div class="modal-header nt-qualite-modal__header">
                <h2 class="modal-title" id="ntQualiteModalLabel">
                    <span class="nt-qualite-modal__icon"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i></span>
                    Qualité du produit
                </h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="ntQualiteModalBody">
                <div class="nt-qp__chargement">
                    <span class="spinner-border text-success" role="status" aria-hidden="true"></span>
                    <p>Chargement des résultats qualité…</p>
                </div>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('ntQualiteModal');
            var body = document.getElementById('ntQualiteModalBody');
            if (!modalEl || !body || typeof bootstrap === 'undefined') return;

            var chargement = body.innerHTML;
            var modal = new bootstrap.Modal(modalEl);

            document.querySelectorAll('[data-nt-qualite-popup]').forEach(function (bouton) {
                bouton.addEventListener('click', function () {
                    body.innerHTML = chargement;
                    modal.show();

                    fetch(bouton.getAttribute('data-url'), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    }).then(function (reponse) {
                        if (!reponse.ok) throw new Error('HTTP ' + reponse.status);
                        return reponse.text();
                    }).then(function (html) {
                        body.innerHTML = html;
                    }).catch(function () {
                        body.innerHTML = '<div class="nt-qp__erreur">'
                            + '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i>'
                            + '<p>Impossible de charger la qualité pour le moment.</p>'
                            + '<a class="btn btn-primary btn-sm" href="{{ route('consommateur.qualite.index') }}">Ouvrir l\u2019espace Qualité</a>'
                            + '</div>';
                    });
                });
            });

            // ── Assistant intelligent du popup ──────────────────────────────
            function normaliser(texte) {
                return (texte || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ' ');
            }

            function bulle(log, texte, type) {
                var div = document.createElement('div');
                div.className = 'nt-msg nt-msg--' + type;
                div.innerHTML = texte;
                log.appendChild(div);
                log.scrollTop = log.scrollHeight;
                return div;
            }

            function donneesAssistant(form) {
                var racine = form.closest('.nt-qp');
                return racine ? racine.dataset : {};
            }

            function reponseAssistant(question, d) {
                var q = normaliser(question);
                var lot = d.lot || '';
                var qualiteUrl = d.qualiteUrl || '';

                // 1) Navigation vers l'espace qualité de ce lot
                if (/(amen|emmene|emmen|ouvre|ouvrir|aller|accede|acceder|montre|consulter|espace qualite|page qualite|voir la qualite)/.test(q)) {
                    if (lot && qualiteUrl) {
                        return {
                            html: 'Bien sûr ! Je vous amène à <strong>votre espace qualité</strong> pour consulter le lot <strong>' + lot + '</strong> de ' + (d.produit || 'ce produit') + '… <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>',
                            rediriger: qualiteUrl
                        };
                    }
                    return {
                        html: 'J\u2019aimerais vous y amener, mais <strong>' + (d.produit || 'ce produit') + ' n\u2019a pas encore de lot enregistré</strong>. Dès qu\u2019un lot sera créé et analysé, je pourrai ouvrir son espace qualité. En attendant, je peux vous donner son statut juste ici.'
                    };
                }

                // 2) Verdict / conformité
                if (/(conforme|verdict|qualite|dangereux|risque|bon|mauvais|sur\b|fiable)/.test(q)) {
                    var txt = '<strong>' + (d.verdict || 'Pas encore analysé') + '</strong> — ' + (d.verdictTexte || '');
                    if ((d.total || '0') !== '0') {
                        txt += '<br>Sur ' + d.total + ' analyse(s) : ' + d.conformes + ' conforme(s), ' + d.nonConformes + ' non conforme(s), ' + d.enAttente + ' en attente' + (d.pct ? ' (' + d.pct + ' % conformes)' : '') + '.';
                    }
                    return { html: txt };
                }

                // 3) Analyses / dernière analyse / laboratoire
                if (/(analyse|laboratoire|labo|combien|derniere|resultat)/.test(q)) {
                    if ((d.total || '0') === '0') {
                        return { html: 'Il n\u2019y a <strong>aucune analyse enregistrée</strong> pour ' + (d.produit || 'ce produit') + ' pour le moment. Les résultats apparaîtront ici dès le premier prélèvement en laboratoire.' };
                    }
                    return { html: 'Dernière analyse : <strong>' + (d.derniere || '—') + '</strong>.<br>Au total : ' + d.total + ' analyse(s), dont ' + d.conformes + ' conforme(s) et ' + d.enAttente + ' en attente.' };
                }

                // 4) Lot
                if (/(lot|numero)/.test(q)) {
                    if (lot) {
                        return { html: 'Le lot consultable de ce produit est <strong>' + lot + '</strong>. Dites « <em>amène-moi à l\u2019espace qualité</em> » et je l\u2019ouvre pour vous.' };
                    }
                    return { html: 'Ce produit n\u2019a <strong>pas encore de lot</strong> : aucun numéro de lot à consulter pour le moment.' };
                }

                // 5) Fiche produit
                if (/(fiche|detail|produit|voir)/.test(q) && d.ficheUrl) {
                    return { html: 'Je vous ouvre la <strong>fiche complète</strong> de ' + (d.produit || 'ce produit') + '…', rediriger: d.ficheUrl };
                }

                // 6) Salutations / remerciements
                if (/(bonjour|salut|merci|bonsoir)/.test(q)) {
                    return { html: 'Avec plaisir ! Demandez-moi le <strong>verdict qualité</strong>, la <strong>dernière analyse</strong>, ou dites « <em>amène-moi à l\u2019espace qualité</em> »' + (lot ? ' du lot <strong>' + lot + '</strong>.' : ' (quand un lot existera).') };
                }

                // 7) Je ne sais pas
                return {
                    html: 'Je ne sais pas encore répondre à ça — je préfère être honnête. Ce que je sais faire : donner le <strong>verdict qualité</strong>, résumer les <strong>analyses</strong>' + (lot ? ', ou vous amener à <strong>l\u2019espace qualité du lot ' + lot + '</strong>.' : '. Dès qu\u2019un lot existera, je pourrai aussi vous y amener.')
                };
            }

            function traiterAssistant(form) {
                var input = form.querySelector('[data-assistant-input]');
                var log = form.closest('.nt-qp__assistant').querySelector('[data-assistant-log]');
                var question = (input.value || '').trim();
                if (!question) return;

                bulle(log, question.replace(/</g, '&lt;'), 'moi');
                input.value = '';

                var saisie = bulle(log, '<span class="nt-typing"><span></span><span></span><span></span></span>', 'bot');
                var d = donneesAssistant(form);

                setTimeout(function () {
                    var reponse = reponseAssistant(question, d);
                    saisie.innerHTML = reponse.html;
                    log.scrollTop = log.scrollHeight;
                    if (reponse.rediriger) {
                        setTimeout(function () { window.location.href = reponse.rediriger; }, 1100);
                    }
                }, 550);
            }

            body.addEventListener('submit', function (e) {
                var form = e.target.closest('[data-assistant-form]');
                if (!form) return;
                e.preventDefault();
                traiterAssistant(form);
            });

            body.addEventListener('click', function (e) {
                var chip = e.target.closest('[data-assistant-question]');
                if (!chip) return;
                var assistant = chip.closest('.nt-qp__assistant');
                var form = assistant.querySelector('[data-assistant-form]');
                form.querySelector('[data-assistant-input]').value = chip.getAttribute('data-assistant-question');
                traiterAssistant(form);
            });
        });
    </script>
@endPushOnce
