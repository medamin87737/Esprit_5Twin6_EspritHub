<section class="page-section" id="contact">
    <div class="container px-4 px-lg-5">
        <x-front.section-heading eyebrow="Contact" title="Parlons de votre chaîne d'approvisionnement">
            Une question, un partenariat, un doute sur un produit ? Écrivez-nous, notre équipe vous répond rapidement.
        </x-front.section-heading>

        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-lg-4">
                <div class="contact-info">
                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                    <div>
                        <h3 class="h6 mb-1">Adresse</h3>
                        <p class="text-muted mb-0">Technopôle El Ghazala, Ariana, Tunisie</p>
                    </div>
                </div>
                <div class="contact-info">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <div>
                        <h3 class="h6 mb-1">E-mail</h3>
                        <p class="mb-0"><a href="mailto:contact@nutritrace.tn">contact@nutritrace.tn</a></p>
                    </div>
                </div>
                <div class="contact-info mb-0">
                    <i class="bi bi-clock" aria-hidden="true"></i>
                    <div>
                        <h3 class="h6 mb-1">Horaires</h3>
                        <p class="text-muted mb-0">Du lundi au vendredi, 8 h 30 – 17 h 30</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="contact-card">
                    @if (session('contact_success'))
                        <div class="alert alert-success d-flex gap-2 align-items-start" role="status">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>{{ session('contact_success') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('contact.store') }}" method="POST" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" type="text" placeholder="Votre nom" value="{{ old('nom') }}" autocomplete="name">
                                    <label for="nom">Nom complet</label>
                                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" placeholder="nom@exemple.com" value="{{ old('email') }}" autocomplete="email">
                                    <label for="email">Adresse e-mail</label>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <select class="form-select @error('profil') is-invalid @enderror" id="profil" name="profil">
                                        <option value="" disabled @selected(! old('profil'))>Choisir…</option>
                                        @foreach (\App\Http\Controllers\ContactController::PROFILS as $value => $label)
                                            <option value="{{ $value }}" @selected(old('profil') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <label for="profil">Vous êtes</label>
                                    @error('profil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" placeholder="Votre message" style="height: 9rem">{{ old('message') }}</textarea>
                                    <label for="message">Message</label>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-grid d-sm-flex justify-content-sm-end mt-4">
                            <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-send me-2" aria-hidden="true"></i>Envoyer le message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
