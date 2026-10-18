@php
    $etape = $etape ?? null;
    $lots = $lots ?? collect();
    $acteurs = $acteurs ?? collect();
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-link-45deg" aria-hidden="true"></i> Rattachement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="lot_id" label="Lot"
                                 :options="$lots->mapWithKeys(fn ($lot) => [$lot->id => $lot->numero_lot . ' · ' . $lot->produit?->nom])"
                                 :value="$etape?->lot_id ?? request('lot')" placeholder="Choisir un lot…" required
                                 empty-message="Aucun lot disponible : créez-en un d'abord." />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="acteur_id" label="Acteur"
                                 :options="$acteurs->mapWithKeys(fn ($acteur) => [$acteur->id => $acteur->nom . ' (' . $acteur->typeActeur?->libelle . ')'])"
                                 :value="$etape?->acteur_id" placeholder="Choisir un acteur…" required
                                 empty-message="Aucun acteur disponible : le module 2 doit d'abord en créer." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-signpost-split" aria-hidden="true"></i> Déroulement</h3>

    {{-- Assistant IA de saisie en langage naturel --}}
    <div class="card border-0 mb-3" style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-left: 4px solid #3b82f6 !important; border-radius: 6px;">
        <div class="card-body p-3">
            <div class="mb-1">
                <span class="font-weight-bold text-dark small">
                    <i class="bi bi-stars text-primary mr-1"></i> Assistant de saisie rapide IA
                </span>
            </div>
            <small class="text-muted d-block mb-2">
                Tapez ou dictez une phrase (ex: <em>« Produit à Zaghouan le 12/10/2026 »</em> ou <em>« Livré à Tunis hier à 14h par camion frigorifique »</em>) pour préremplir automatiquement le formulaire.
            </small>
            <div class="input-group input-group-sm">
                <input type="text" id="ia-prompt-input" class="form-control" placeholder="Décrivez l'étape en langage naturel (ex: arrivé à Sousse hier par camion)...">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" id="btn-ia-remplir">
                        <i class="bi bi-magic mr-1"></i> Remplir par IA
                    </button>
                </div>
            </div>
            <div id="ia-feedback" class="mt-1 small" style="display: none;"></div>
        </div>
    </div>

    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="type_etape" label="Type d'étape" :options="config('nutritrace.options.etape_types')"
                                 :value="$etape?->type_etape" placeholder="Choisir un type…" required />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="date_heure" type="datetime-local" label="Date et heure"
                                :value="$etape?->date_heure?->format('Y-m-d\TH:i')" required />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="lieu" label="Lieu" :value="$etape?->lieu" required placeholder="Ex. Usine de Ben Arous" />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="mode_transport" label="Mode de transport" :options="config('nutritrace.options.modes_transport')"
                                 :value="$etape?->mode_transport" placeholder="Choisir un mode…" required />
        </div>
    </div>

    {{-- Sélecteur interactif de lieu sur carte Leaflet / OpenStreetMap --}}
    <div class="card bg-light border-0 mb-3">
        <div class="card-body p-3">
            <div class="mb-2">
                <label class="font-weight-bold text-dark mb-0">
                    <i class="bi bi-geo-alt-fill text-success mr-1"></i> Sélectionner le lieu sur la carte
                </label>
                <small class="d-block text-muted">
                    Cliquez sur la carte ou recherchez une ville pour renseigner automatiquement le champ « Lieu ».
                </small>
            </div>

            <div class="input-group input-group-sm mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                </div>
                <input type="text" id="map-search-input" class="form-control" placeholder="Rechercher une ville, adresse… (ex: Tunis, Sousse, Rungis)">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="btn-map-search">Rechercher</button>
                </div>
            </div>

            <div id="etape-map-picker" style="height: 280px; width: 100%; border-radius: 6px; border: 1px solid #d1d3e2; z-index: 1;"></div>

            <div class="d-flex justify-content-between align-items-center mt-2 px-1 small">
                <span id="map-picker-status" class="text-muted">
                    <i class="bi bi-cursor mr-1"></i> Cliquez sur la carte pour pointer l'adresse exacte.
                </span>
                <span id="map-picker-coords" class="text-secondary font-monospace"></span>
            </div>

            {{-- Coordonnées GPS invisibles (champs cachés envoyés automatiquement au serveur) --}}
            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $etape?->latitude) }}">
            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $etape?->longitude) }}">
        </div>
    </div>

    <x-admin.form.textarea name="remarques" label="Remarques" :value="$etape?->remarques" rows="3"
                           placeholder="Température, conditions particulières…" help="Facultatif." />
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #etape-map-picker {
        position: relative;
    }
    #etape-map-picker .leaflet-popup-content-wrapper {
        border-radius: 6px;
        font-size: 0.85rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('etape-map-picker');
    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const lieuInput = document.getElementById('lieu');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const searchInput = document.getElementById('map-search-input');
    const searchBtn = document.getElementById('btn-map-search');
    const statusEl = document.getElementById('map-picker-status');
    const coordsEl = document.getElementById('map-picker-coords');

    // Coordonnées par défaut (Tunisie)
    let initialLat = 36.8065;
    let initialLng = 10.1815;
    let initialZoom = 7;
    let currentMarker = null;

    const map = L.map('etape-map-picker', {
        scrollWheelZoom: true
    }).setView([initialLat, initialLng], initialZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
    }).addTo(map);

    function formaterAdresse(data) {
        if (!data) return '';
        const addr = data.address || {};
        const nomLieu = data.name || '';
        const voie = addr.road || addr.pedestrian || addr.suburb || addr.industrial || addr.commercial || '';
        const ville = addr.city || addr.town || addr.village || addr.municipality || addr.county || '';
        const pays = addr.country || '';

        const elements = [];
        if (nomLieu && nomLieu !== voie && nomLieu !== ville) {
            elements.push(nomLieu);
        }
        if (voie) elements.push(voie);
        if (ville) elements.push(ville);
        if (pays && elements.length < 2) elements.push(pays);

        let res = elements.join(', ');
        if (!res || res.length < 3) {
            res = data.display_name || '';
        }
        if (res.length > 140) {
            res = res.substring(0, 137) + '...';
        }
        return res;
    }

    function setMarqueur(lat, lng, adresseTexte = null, zoom = 14) {
        if (currentMarker) {
            currentMarker.setLatLng([lat, lng]);
        } else {
            currentMarker = L.marker([lat, lng], { draggable: true }).addTo(map);
            currentMarker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                geocoderInverse(pos.lat, pos.lng);
            });
        }

        map.setView([lat, lng], zoom);
        if (coordsEl) {
            coordsEl.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }

        // Remplir silencieusement les coordonnées cachées
        if (latInput) latInput.value = Number(lat).toFixed(8);
        if (lngInput) lngInput.value = Number(lng).toFixed(8);

        if (adresseTexte) {
            if (lieuInput) {
                lieuInput.value = adresseTexte;
            }
            currentMarker.bindPopup(`<strong>${adresseTexte}</strong><br><small class="text-muted">Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}</small>`).openPopup();
            if (statusEl) {
                statusEl.innerHTML = `<i class="bi bi-check-circle-fill text-success mr-1"></i> Lieu sélectionné : <strong>${adresseTexte}</strong>`;
            }
        }
    }

    async function geocoderInverse(lat, lng) {
        if (coordsEl) {
            coordsEl.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
        if (statusEl) {
            statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-success mr-1" role="status"></span> Recherche de l'adresse…`;
        }

        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
            const response = await fetch(url, {
                headers: { 'Accept-Language': 'fr, ar, en' }
            });
            if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
            const data = await response.json();
            const texte = formaterAdresse(data);

            if (texte) {
                setMarqueur(lat, lng, texte, map.getZoom());
            } else {
                const fallback = `Point GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                setMarqueur(lat, lng, fallback, map.getZoom());
            }
        } catch (err) {
            console.warn('Erreur geocodage inverse:', err);
            const fallback = (lieuInput && lieuInput.value) ? lieuInput.value : `Point GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            setMarqueur(lat, lng, fallback, map.getZoom());
            if (statusEl) {
                statusEl.innerHTML = `<i class="bi bi-geo-alt-fill text-primary mr-1"></i> Point positionné (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            }
        }
    }

    map.on('click', function (e) {
        geocoderInverse(e.latlng.lat, e.latlng.lng);
    });

    async function lancerRecherche(terme = null) {
        const query = (terme || (searchInput ? searchInput.value : '') || '').trim();
        if (!query) return;

        if (searchInput && terme) {
            searchInput.value = terme;
        }

        if (statusEl) {
            statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-primary mr-1" role="status"></span> Recherche de « ${query} »…`;
        }

        try {
            const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&limit=1`;
            const response = await fetch(url, {
                headers: { 'Accept-Language': 'fr, ar, en' }
            });
            if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
            const resultats = await response.json();

            if (resultats && resultats.length > 0) {
                const item = resultats[0];
                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);
                const texte = formaterAdresse(item);
                setMarqueur(lat, lng, texte, 14);
            } else {
                if (statusEl) {
                    statusEl.innerHTML = `<i class="bi bi-exclamation-triangle text-warning mr-1"></i> Aucun résultat trouvé pour « ${query} ». Essayez un autre nom de ville.`;
                }
            }
        } catch (err) {
            console.warn('Erreur recherche:', err);
            if (statusEl) {
                statusEl.innerHTML = `<i class="bi bi-exclamation-circle text-danger mr-1"></i> Impossible de contacter le service de recherche.`;
            }
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            lancerRecherche();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lancerRecherche();
            }
        });
    }

    // Assistant IA de saisie rapide en langage naturel
    const iaInput = document.getElementById('ia-prompt-input');
    const iaBtn = document.getElementById('btn-ia-remplir');
    const iaFeedback = document.getElementById('ia-feedback');
    const typeSelect = document.getElementById('type_etape');
    const dateInput = document.getElementById('date_heure');
    const transportSelect = document.getElementById('mode_transport');
    const remarquesInput = document.getElementById('remarques');

    if (iaBtn && iaInput) {
        async function executerAssistantIa() {
            const prompt = (iaInput.value || '').trim();
            if (!prompt) return;

            iaBtn.disabled = true;
            iaBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Analyse…';
            if (iaFeedback) {
                iaFeedback.style.display = 'block';
                iaFeedback.className = 'mt-1 small text-primary';
                iaFeedback.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i> Analyse intelligente de votre phrase…';
            }

            try {
                const csrfToken = document.querySelector('input[name="_token"]')?.value
                    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const response = await fetch('{{ route("admin.etapes.assistant-ai") }}', {
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
                    if (typeSelect && d.type_etape) typeSelect.value = d.type_etape;
                    if (dateInput && d.date_heure) dateInput.value = d.date_heure;
                    if (transportSelect && d.mode_transport) transportSelect.value = d.mode_transport;
                    if (remarquesInput && d.remarques && !remarquesInput.value) remarquesInput.value = d.remarques;

                    if (d.lieu) {
                        lieuInput.value = d.lieu;
                        lancerRecherche(d.lieu);
                    }

                    if (iaFeedback) {
                        iaFeedback.className = 'mt-1 small text-success font-weight-bold';
                        iaFeedback.innerHTML = '<i class="bi bi-check-circle-fill mr-1"></i> Formulaire prérempli avec succès par l\'IA !';
                    }
                } else {
                    throw new Error('Réponse invalide');
                }
            } catch (err) {
                console.warn('Erreur assistant IA:', err);
                if (iaFeedback) {
                    iaFeedback.className = 'mt-1 small text-danger';
                    iaFeedback.innerHTML = '<i class="bi bi-exclamation-circle mr-1"></i> Erreur lors de l\'analyse par l\'IA.';
                }
            } finally {
                iaBtn.disabled = false;
                iaBtn.innerHTML = '<i class="bi bi-magic mr-1"></i> Remplir par IA';
            }
        }

        iaBtn.addEventListener('click', executerAssistantIa);
        iaInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                executerAssistantIa();
            }
        });
    }

    if (latInput && latInput.value && lngInput && lngInput.value) {
        const savedLat = parseFloat(latInput.value);
        const savedLng = parseFloat(lngInput.value);
        if (!isNaN(savedLat) && !isNaN(savedLng)) {
            setMarqueur(savedLat, savedLng, lieuInput ? lieuInput.value : null, 14);
        }
    } else if (lieuInput && lieuInput.value) {
        if (statusEl) {
            statusEl.innerHTML = `<i class="bi bi-geo-alt-fill text-success mr-1"></i> Lieu actuel : <strong>${lieuInput.value}</strong>`;
        }
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 250);
});
</script>
@endpush
