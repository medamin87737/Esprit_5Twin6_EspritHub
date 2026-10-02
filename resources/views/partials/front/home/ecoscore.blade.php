<section class="page-section" id="ecoscore">
    <div class="container px-4 px-lg-5">
        <div class="row gx-lg-5 gy-5 align-items-center">
            <div class="col-lg-6 reveal">
                <x-front.section-heading eyebrow="Éco-score" title="L'impact d'un produit, lisible en un coup d'œil" align="start" class="mb-4">
                    Calculé à partir des émissions de chaque étape, le score de A à E permet de comparer objectivement deux produits.
                </x-front.section-heading>

                <ul class="check-list">
                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Mesuré, pas déclaré :</strong> chaque indicateur est rattaché à une étape réelle du parcours.</span></li>
                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Quatre postes d'impact :</strong> production, transformation, transport et emballage.</span></li>
                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Comparable :</strong> le même barème pour tous les produits d'une catégorie.</span></li>
                </ul>
            </div>

            <div class="col-lg-6 reveal reveal-delay-1">
                <div class="eco-card">
                    <div class="eco-card-header">
                        <img src="{{ Vite::asset('resources/assets/front/img/producteur-vaches.webp') }}" alt="Vaches laitières en pâturage">
                        <div class="flex-grow-1">
                            <span class="demo-tag mb-1">Exemple de démonstration</span>
                            <div class="small text-muted">Laiterie Délice · Lot LOT-2026-0042</div>
                            <h3 class="h5 mb-2">Yaourt nature bio 125 g</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="label-chip"><i class="bi bi-flower1"></i> Bio</span>
                                <span class="label-chip"><i class="bi bi-geo-alt"></i> Local</span>
                            </div>
                        </div>
                    </div>
                    <div class="eco-card-body">
                        <div class="eco-scale" aria-label="Éco-score B">
                            <span class="eco-a">A</span>
                            <span class="eco-b is-active">B</span>
                            <span class="eco-c">C</span>
                            <span class="eco-d">D</span>
                            <span class="eco-e">E</span>
                        </div>

                        <div class="eco-kpis">
                            <div class="eco-kpi"><strong>1,2 kg</strong><span>CO₂e / kg</span></div>
                            <div class="eco-kpi"><strong>110 km</strong><span>parcourus</span></div>
                            <div class="eco-kpi"><strong>4</strong><span>étapes tracées</span></div>
                        </div>

                        <div class="eco-bars">
                            <div class="eco-bar">
                                <div class="eco-bar-label"><span>Production agricole</span><strong>58 %</strong></div>
                                <div class="eco-bar-track"><div class="eco-bar-fill" style="--value: 58%"></div></div>
                            </div>
                            <div class="eco-bar">
                                <div class="eco-bar-label"><span>Transformation</span><strong>17 %</strong></div>
                                <div class="eco-bar-track"><div class="eco-bar-fill" style="--value: 17%"></div></div>
                            </div>
                            <div class="eco-bar">
                                <div class="eco-bar-label"><span>Transport</span><strong>15 %</strong></div>
                                <div class="eco-bar-track"><div class="eco-bar-fill" style="--value: 15%"></div></div>
                            </div>
                            <div class="eco-bar mb-0">
                                <div class="eco-bar-label"><span>Emballage</span><strong>10 %</strong></div>
                                <div class="eco-bar-track"><div class="eco-bar-fill" style="--value: 10%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
