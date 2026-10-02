<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Se déconnecter ?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-muted">Votre session d'administration sera fermée sur cet appareil.</div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-dismiss="modal">Annuler</button>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-right mr-1" aria-hidden="true"></i> Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
