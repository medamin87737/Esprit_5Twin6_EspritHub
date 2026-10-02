@extends('layouts.front')

@section('title', 'Vérifier un label')

@section('content')
    @php
        $certification = $certification ?? null;
        $certifications = $certifications ?? collect();
        $numero = trim((string) request('numero'));
        $types = config('nutritrace.options.certification_types');
        $statuts = config('nutritrace.options.certification_statuts');
        $statutClasses = ['valide' => 'status-valid', 'expiree' => 'status-expired', 'suspendue' => 'status-suspended'];
    @endphp

    <x-front.page-header eyebrow="Labels" title="Ce label est-il valide ?" image="consommateur-salade.webp"
                         subtitle="Vérifiez en quelques secondes qu'une certification bio, locale ou équitable est authentique et toujours en cours de validité." />

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
            </form>

            @if ($certification)
                @php($estValide = $certification->statut === 'valide' && ! $certification->date_expiration?->isPast())
                <div class="verify-result {{ $estValide ? 'is-valid' : 'is-invalid' }}">
                    <span class="verify-result-icon"><i class="bi {{ $estValide ? 'bi-patch-check-fill' : 'bi-x-octagon-fill' }}" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="h5 mb-1">{{ $estValide ? 'Certification valide' : 'Certification non valide' }}</h2>
                        <p class="mb-2">
                            Label <strong>{{ $types[$certification->type] ?? $certification->type }}</strong> n° {{ $certification->numero }}
                            pour <strong>{{ $certification->produit?->nom }}</strong>, délivré par {{ $certification->organisme?->nom }}.
                        </p>
                        <span class="status-pill {{ $statutClasses[$certification->statut] ?? '' }}">{{ $statuts[$certification->statut] ?? $certification->statut }}</span>
                        <span class="small text-muted ms-2">Valable du {{ $certification->date_obtention?->format('d/m/Y') }} au {{ $certification->date_expiration?->format('d/m/Y') }}</span>
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
                <span class="text-muted small">{{ $certifications->count() }} certification{{ $certifications->count() > 1 ? 's' : '' }}</span>
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($certifications as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item->numero }}</td>
                                        <td><span class="label-chip">{{ $types[$item->type] ?? $item->type }}</span></td>
                                        <td>{{ $item->produit?->nom }}</td>
                                        <td class="text-muted">{{ $item->organisme?->nom }}</td>
                                        <td class="text-muted">{{ $item->date_expiration?->format('d/m/Y') }}</td>
                                        <td><span class="status-pill {{ $statutClasses[$item->statut] ?? '' }}">{{ $statuts[$item->statut] ?? $item->statut }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
