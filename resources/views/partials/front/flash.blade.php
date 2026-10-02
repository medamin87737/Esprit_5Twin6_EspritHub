@if (session('status') && ! request()->routeIs('profile.*'))
    <div class="toast-container position-fixed end-0 p-3 flash-container">
        <div class="toast flash-toast show" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="6000">
            <div class="d-flex align-items-start gap-2 p-3">
                <i class="bi bi-check-circle-fill flash-icon" aria-hidden="true"></i>
                <div class="flex-grow-1">{{ session('status') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        </div>
    </div>
@endif
