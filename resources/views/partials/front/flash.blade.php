@php
    $messages = array_filter([
        'success' => request()->routeIs('profile.*') ? null : session('status'),
        'error' => session('error'),
    ]);
@endphp

@if ($messages)
    <div class="toast-container position-fixed end-0 p-3 flash-container">
        @foreach ($messages as $type => $message)
            <div class="toast flash-toast flash-{{ $type }} show" role="{{ $type === 'error' ? 'alert' : 'status' }}" aria-live="{{ $type === 'error' ? 'assertive' : 'polite' }}" aria-atomic="true" data-bs-delay="6000">
                <div class="d-flex align-items-start gap-2 p-3">
                    <i class="bi {{ $type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }} flash-icon" aria-hidden="true"></i>
                    <div class="flex-grow-1">{{ $message }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>
                </div>
            </div>
        @endforeach
    </div>
@endif
