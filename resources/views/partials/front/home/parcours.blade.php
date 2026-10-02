<section class="page-section bg-cream" id="parcours">
    <div class="container px-4 px-lg-5">
        <x-front.section-heading eyebrow="Traçabilité" title="Le parcours d'un produit, étape par étape">
            Chaque acteur enregistre son intervention : le consommateur obtient une chronologie complète et vérifiable.
        </x-front.section-heading>

        <div class="journey-head">
            <span class="demo-tag">Exemple de démonstration</span>
            <span class="lot-ticket"><i class="bi bi-upc" aria-hidden="true"></i> <strong>LOT-2026-0042</strong> · Yaourt nature bio 125 g</span>
        </div>

        <ol class="timeline reveal" aria-label="Étapes du parcours du lot d'exemple">
            <x-front.timeline-step number="1" icon="bi-flower2" title="Production agricole" status="Vérifié" meta="01/10 · Béja">
                Collecte de lait bio à la Ferme Ben Salah, vaches nourries à l'herbe.
            </x-front.timeline-step>
            <x-front.timeline-step number="2" icon="bi-gear-wide-connected" title="Collecte et transformation" status="Vérifié" meta="02/10 · Mateur">
                Pasteurisation et fermentation à la Laiterie Délice.
            </x-front.timeline-step>
            <x-front.timeline-step number="3" icon="bi-clipboard2-check" title="Contrôle qualité" status="Conforme" meta="02/10 · Laboratoire">
                Analyses microbiologiques et contrôle de conformité du lot.
            </x-front.timeline-step>
            <x-front.timeline-step number="4" icon="bi-truck" title="Transport et distribution" status="Vérifié" meta="03/10 · 72 km">
                Transport frigorifique à 4 °C vers la plateforme logistique.
            </x-front.timeline-step>
            <x-front.timeline-step number="5" icon="bi-shop" title="Point de vente" status="En cours" status-type="pending" meta="04/10 · Tunis">
                Mise en rayon et consultation du parcours par le consommateur.
            </x-front.timeline-step>
        </ol>

        <div class="journey-foot">
            <p class="mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i> Données fictives présentées à titre d'illustration. Les parcours réels proviennent du module Traçabilité des lots.</p>
            @if (Route::has('front.lots.search'))
                <a class="btn btn-primary" href="{{ route('front.lots.search') }}">Rechercher un lot <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
            @endif
        </div>
    </div>
</section>
