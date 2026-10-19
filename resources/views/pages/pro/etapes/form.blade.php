@extends('layouts.pro')

@php($edition = $etape->exists)

@section('title', $edition ? 'Modifier mon étape' : 'Ajouter mon étape')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('pro.lots.show', $lot) }}" class="small d-inline-block mb-3"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Lot {{ $lot->numero_lot }}</a>

            <form method="POST" class="account-card" novalidate
                  action="{{ $edition ? route('pro.etapes.update', $etape) : route('pro.etapes.store', $lot) }}">
                @csrf
                @if ($edition)
                    @method('PUT')
                @endif

                <h2 class="account-card-title"><i class="bi bi-signpost-split" aria-hidden="true"></i> {{ $lot->produit?->nom }} · {{ $lot->numero_lot }}</h2>

                {{-- Assistant IA de saisie en langage naturel --}}
                <div class="card border-0 mb-4 shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-left: 4px solid #3b82f6 !important; border-radius: 8px;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small">
                                <i class="bi bi-stars text-primary me-1"></i> Assistant de saisie rapide IA
                            </span>
                        </div>
                        <small class="text-muted d-block mb-2">
                            Tapez votre étape en langage naturel (ex: <em>« Produit à Béja le 15/10/2026 à 08h »</em> ou <em>« Livré à Tunis ce matin par camion frigorifique »</em>) pour préremplir automatiquement le formulaire et pointer la carte.
                        </small>
                        <div class="input-group input-group-sm">
                            <input type="text" id="ia-prompt-input" class="form-control" placeholder="Décrivez l'étape (lieu, date, transport, détails)...">
                            <button class="btn btn-primary" type="button" id="btn-ia-remplir">
                                <i class="bi bi-magic me-1"></i> Préremplir par IA
                            </button>
                        </div>
                        <div id="ia-feedback" class="mt-2 small" style="display: none;"></div>
                    </div>
                </div>

                <div class="row gx-3">
                    <x-front.champ class="col-md-6" name="type_etape" label="Type d'étape" type="select" required :options="$types"
                                   :value="$etape->type_etape ?? (count($types) === 1 ? array_key_first($types) : null)"
                                   help="Limité aux étapes autorisées pour votre rôle." />
                    <x-front.champ class="col-md-6" name="date_heure" label="Date et heure" type="datetime-local" required
                                   :value="$etape->date_heure?->format('Y-m-d\TH:i')" />
                    <x-front.champ class="col-md-7" name="lieu" label="Lieu" required :value="$etape->lieu" maxlength="150" />
                    <x-front.champ class="col-md-5" name="mode_transport" label="Mode de transport" type="select" required
                                   :value="$etape->mode_transport" :options="config('nutritrace.options.modes_transport')" />
                </div>

                {{-- Sélecteur interactif de lieu sur carte Leaflet / OpenStreetMap --}}
                <div class="card bg-light border-0 mb-3 shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="fw-bold text-dark mb-0 small">
                                    <i class="bi bi-geo-alt-fill text-success me-1"></i> Sélectionner le lieu sur la carte
                                </label>
                                <small class="d-block text-muted">
                                    Cliquez sur la carte ou recherchez une ville pour renseigner automatiquement le champ « Lieu » et ses coordonnées GPS.
                                </small>
                            </div>
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="map-search-input" class="form-control" placeholder="Rechercher une ville, adresse… (ex: Tunis, Béja, Sfax)">
                            <button class="btn btn-outline-secondary" type="button" id="btn-map-search">Rechercher</button>
                        </div>

                        <div id="etape-map-picker" style="height: 260px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6; z-index: 1;"></div>

                        <div class="d-flex justify-content-between align-items-center mt-2 px-1 small">
                            <span id="map-picker-status" class="text-muted">
                                <i class="bi bi-cursor me-1"></i> Cliquez sur la carte pour pointer l'adresse exacte.
                            </span>
                            <span id="map-picker-coords" class="text-secondary font-monospace"></span>
                        </div>

                        {{-- Coordonnées GPS invisibles (champs cachés envoyés automatiquement au serveur) --}}
                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $etape?->latitude) }}">
                        <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $etape?->longitude) }}">
                    </div>
                </div>

                <x-front.champ name="remarques" label="Remarques" type="textarea" rows="3" :value="$etape->remarques" maxlength="1000"
                               placeholder="Conditions de stockage, contrôle qualité…" />

                <div class="form-actions">
                    <a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">{{ $edition ? 'Enregistrer' : 'Ajouter l\'étape' }}</button>
                </div>
            </form>
        </div>
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
                const form = mapElement ? mapElement.closest('form') : null;
                if (!mapElement || !form || typeof L === 'undefined') {
                    return;
                }

                const lieuInput = form.querySelector('[name="lieu"]');
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const searchInput = document.getElementById('map-search-input');
                const searchBtn = document.getElementById('btn-map-search');
                const statusEl = document.getElementById('map-picker-status');
                const coordsEl = document.getElementById('map-picker-coords');

                const iaBtn = document.getElementById('btn-ia-remplir');
                const iaInput = document.getElementById('ia-prompt-input');
                const iaFeedback = document.getElementById('ia-feedback');

                const typeSelect = form.querySelector('[name="type_etape"]');
                const dateInput = form.querySelector('[name="date_heure"]');
                const transportSelect = form.querySelector('[name="mode_transport"]');
                const remarquesInput = form.querySelector('[name="remarques"]');

                // Initialisation Carte Leaflet (Tunisie par défaut)
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

                    if (latInput) latInput.value = Number(lat).toFixed(8);
                    if (lngInput) lngInput.value = Number(lng).toFixed(8);

                    if (adresseTexte) {
                        if (lieuInput) {
                            lieuInput.value = adresseTexte;
                        }
                        currentMarker.bindPopup(`<strong>${adresseTexte}</strong><br><small class="text-muted">GPS: ${lat.toFixed(4)}, ${lng.toFixed(4)}</small>`).openPopup();
                        if (statusEl) {
                            statusEl.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Lieu sélectionné : <strong>${adresseTexte}</strong>`;
                        }
                    }
                }

                async function geocoderInverse(lat, lng) {
                    if (coordsEl) {
                        coordsEl.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    }
                    if (statusEl) {
                        statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-success me-1" role="status"></span> Recherche de l'adresse…`;
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
                            statusEl.innerHTML = `<i class="bi bi-geo-alt-fill text-primary me-1"></i> Point positionné (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                        }
                    }
                }

                async function lancerRecherche(texte) {
                    const query = (texte || (searchInput ? searchInput.value : '')).trim();
                    if (!query) return;

                    if (statusEl) {
                        statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span> Recherche de « ${query} »…`;
                    }
                    if (searchBtn) searchBtn.disabled = true;

                    try {
                        const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&countrycodes=tn,fr,dz,ly&limit=1`;
                        const response = await fetch(url, {
                            headers: { 'Accept-Language': 'fr, ar, en' }
                        });
                        if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
                        const data = await response.json();

                        if (data && data.length > 0) {
                            const item = data[0];
                            const lat = parseFloat(item.lat);
                            const lng = parseFloat(item.lon);
                            const nom = item.name || query;
                            setMarqueur(lat, lng, nom, 13);
                        } else {
                            if (statusEl) {
                                statusEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Aucun résultat pour « ${query} ». Cliquez directement sur la carte.`;
                            }
                        }
                    } catch (err) {
                        console.warn('Erreur recherche geocodage:', err);
                        if (statusEl) {
                            statusEl.innerHTML = `<i class="bi bi-exclamation-circle text-danger me-1"></i> Erreur lors de la recherche. Cliquez directement sur la carte.`;
                        }
                    } finally {
                        if (searchBtn) searchBtn.disabled = false;
                    }
                }

                map.on('click', function (e) {
                    geocoderInverse(e.latlng.lat, e.latlng.lng);
                });

                if (searchBtn) {
                    searchBtn.addEventListener('click', function () {
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

                // Assistant IA
                if (iaBtn && iaInput) {
                    async function executerAssistantIa() {
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

                            const response = await fetch('{{ route("pro.etapes.assistant-ai") }}', {
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
                                if (typeSelect && d.type_etape) {
                                    const opt = typeSelect.querySelector(`option[value="${d.type_etape}"]`);
                                    if (opt) typeSelect.value = d.type_etape;
                                }
                                if (dateInput && d.date_heure) dateInput.value = d.date_heure;
                                if (transportSelect && d.mode_transport) transportSelect.value = d.mode_transport;
                                if (remarquesInput && d.remarques && !remarquesInput.value) remarquesInput.value = d.remarques;

                                if (d.lieu) {
                                    if (lieuInput) lieuInput.value = d.lieu;
                                    lancerRecherche(d.lieu);
                                }

                                if (iaFeedback) {
                                    iaFeedback.className = 'mt-2 small text-success fw-bold';
                                    iaFeedback.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Formulaire et carte préremplis avec succès par l\'IA !';
                                }
                            } else {
                                throw new Error('Réponse invalide');
                            }
                        } catch (err) {
                            console.warn('Erreur assistant IA pro:', err);
                            if (iaFeedback) {
                                iaFeedback.className = 'mt-2 small text-danger';
                                iaFeedback.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Erreur lors de l\'analyse par l\'IA.';
                            }
                        } finally {
                            iaBtn.disabled = false;
                            iaBtn.innerHTML = '<i class="bi bi-magic me-1"></i> Préremplir par IA';
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

                // Restaurer les coordonnées existantes ou adresse par défaut
                if (latInput && latInput.value && lngInput && lngInput.value) {
                    const savedLat = parseFloat(latInput.value);
                    const savedLng = parseFloat(lngInput.value);
                    if (!isNaN(savedLat) && !isNaN(savedLng)) {
                        setMarqueur(savedLat, savedLng, lieuInput ? lieuInput.value : null, 14);
                    }
                } else if (lieuInput && lieuInput.value) {
                    lancerRecherche(lieuInput.value);
                }

                setTimeout(function () {
                    map.invalidateSize();
                }, 300);
            });
        </script>
    @endpush
@endsection
