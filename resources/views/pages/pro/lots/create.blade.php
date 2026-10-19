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

                    {{-- Assistant IA de saisie en langage naturel --}}
                    <div class="card border-0 mb-4 shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-left: 4px solid #3b82f6 !important; border-radius: 8px;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small">
                                    <i class="bi bi-stars text-primary me-1"></i> Assistant de saisie rapide IA
                                </span>
                            </div>
                            <small class="text-muted d-block mb-2">
                                Décrivez votre lot en langage naturel (ex: <em>« 500 unités produites aujourd'hui et périment dans 6 mois »</em>) pour préremplir automatiquement le formulaire.
                            </small>
                            <div class="input-group input-group-sm">
                                <input type="text" id="ia-prompt-lot" class="form-control" placeholder="Décrivez le lot (quantité, date production, péremption)...">
                                <button class="btn btn-primary" type="button" id="btn-ia-remplir-lot">
                                    <i class="bi bi-magic me-1"></i> Préremplir par IA
                                </button>
                            </div>
                            <div id="ia-feedback-lot" class="mt-2 small" style="display: none;"></div>
                        </div>
                    </div>

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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const iaBtn = document.getElementById('btn-ia-remplir-lot');
                const iaInput = document.getElementById('ia-prompt-lot');
                const iaFeedback = document.getElementById('ia-feedback-lot');

                const form = iaBtn ? iaBtn.closest('form') : null;
                if (!iaBtn || !iaInput || !form) return;

                const produitSelect = form.querySelector('[name="produit_id"]');
                const qteInput = form.querySelector('[name="quantite"]');
                const dateProdInput = form.querySelector('[name="date_production"]');
                const datePeremptInput = form.querySelector('[name="date_peremption"]');

                async function executerAssistantIaLot() {
                    const prompt = (iaInput.value || '').trim();
                    if (!prompt) return;

                    iaBtn.disabled = true;
                    iaBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analyse…';
                    if (iaFeedback) {
                        iaFeedback.style.display = 'block';
                        iaFeedback.className = 'mt-2 small text-primary';
                        iaFeedback.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Analyse intelligente de votre phrase…';
                    }

                    try {
                        const csrfToken = form.querySelector('input[name="_token"]')?.value
                            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                        const response = await fetch('{{ route("pro.lots.assistant-ai") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ prompt: prompt })
                        });

                        const res = await response.json();
                        if (res.success && res.data) {
                            const d = res.data;
                            if (produitSelect && d.produit_id) {
                                produitSelect.value = d.produit_id;
                            }
                            if (qteInput && d.quantite) qteInput.value = d.quantite;
                            if (dateProdInput && d.date_production) dateProdInput.value = d.date_production;
                            if (datePeremptInput && d.date_peremption) datePeremptInput.value = d.date_peremption;

                            if (iaFeedback) {
                                iaFeedback.className = 'mt-2 small text-success fw-bold';
                                iaFeedback.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Formulaire prérempli avec succès par l\'IA !';
                            }
                        } else {
                            throw new Error('Réponse invalide');
                        }
                    } catch (err) {
                        console.warn('Erreur assistant IA lot pro:', err);
                        if (iaFeedback) {
                            iaFeedback.className = 'mt-2 small text-danger';
                            iaFeedback.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Erreur lors de l\'analyse par l\'IA.';
                        }
                    } finally {
                        iaBtn.disabled = false;
                        iaBtn.innerHTML = '<i class="bi bi-magic me-1"></i> Préremplir par IA';
                    }
                }

                iaBtn.addEventListener('click', executerAssistantIaLot);
                iaInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        executerAssistantIaLot();
                    }
                });
            });
        </script>
    @endpush
@endsection
