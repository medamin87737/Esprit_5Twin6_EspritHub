@extends('layouts.pro')

@section('title', 'Nouveau lot')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if ($produits->isEmpty())
                <x-front.empty-state icon="bi-box-seam" title="Ajoutez d'abord un produit">
                    Un lot est toujours rattaché à l'un de vos produits.
                </x-front.empty-state>
                <div class="text-center mt-3">
                    <a href="{{ route('pro.produits.create') }}" class="btn btn-primary rounded-pill px-4">Nouveau produit</a>
                </div>
            @else
                <form method="POST" action="{{ route('pro.lots.store') }}" class="account-card" novalidate>
                    @csrf
                    <h2 class="account-card-title"><i class="bi bi-upc-scan" aria-hidden="true"></i> Informations du lot</h2>

                    <div class="row gx-3">
                        <x-front.champ class="col-md-7" name="produit_id" label="Produit" type="select" required :options="$produits" placeholder="Choisir un de vos produits…" />
                        <x-front.champ class="col-md-5" name="numero_lot" label="Numéro de lot" required :value="$numeroSuggere"
                                       help="Format LOT-AAAA-NNNN, imprimé sur l'emballage." maxlength="30" />
                        <x-front.champ class="col-md-4" name="quantite" label="Quantité (unités)" type="number" required min="1" step="1" />
                        <x-front.champ class="col-md-4" name="date_production" label="Date de production" type="date" required :value="today()->format('Y-m-d')" />
                        <x-front.champ class="col-md-4" name="date_peremption" label="Date de péremption" type="date" required />
                    </div>

                    <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Vous serez l'acteur courant du lot : ajoutez ensuite votre étape de {{ $etapeInitiale }}, puis transférez-le à l'acteur suivant.</p>

                    <div class="form-actions">
                        <a href="{{ route('pro.lots.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">Créer le lot</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
