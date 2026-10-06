<section class="nt-welcome">
    <div class="d-flex flex-wrap justify-content-between align-items-end" style="gap: 1.25rem;">
        <div style="position: relative; z-index: 1;">
            <span class="nt-eyebrow">Back Office</span>
            <h1>Bonjour {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</h1>
            <p class="mb-0">Pilotez les cinq modules de NutriTrace : catalogue, analyses qualité, lots, empreinte et certifications.</p>
        </div>
        <div class="nt-welcome-actions">
            <a href="{{ route('home') }}" class="btn nt-btn-glass" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Voir le site
            </a>
            <a href="{{ route('admin.produits.create') }}" class="btn btn-gold">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau produit
            </a>
        </div>
    </div>
</section>
