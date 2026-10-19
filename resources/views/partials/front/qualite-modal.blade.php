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
        });
    </script>
@endPushOnce
