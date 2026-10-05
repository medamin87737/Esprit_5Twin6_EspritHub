<div class="modal fade" id="scanModal" tabindex="-1" aria-labelledby="scanModalTitre" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content scan-modal">
            <div class="modal-header">
                <h2 class="modal-title h5" id="scanModalTitre"><i class="bi bi-camera me-2" aria-hidden="true"></i>Scanner un lot</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="scan-video">
                    <video playsinline muted></video>
                    <span class="scan-viseur" aria-hidden="true"></span>
                </div>
                <p class="small text-muted mt-3 mb-0">Placez le code imprimé sur l'emballage dans le cadre.</p>
                <div class="alert alert-warning small mt-3 mb-0" data-scan-message hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Saisir le numéro à la main</button>
            </div>
        </div>
    </div>
</div>
