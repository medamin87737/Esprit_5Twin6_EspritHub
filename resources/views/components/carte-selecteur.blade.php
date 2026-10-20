@props([
    'latitude' => null,
    'longitude' => null,
    'id' => 'carte-selecteur',
    'label' => 'Position sur la carte',
    'help' => null,
])

@php
    $latValeur = old('latitude', $latitude);
    $lngValeur = old('longitude', $longitude);
@endphp

<div class="carte-selecteur" data-carte-selecteur="{{ $id }}">
    <span class="carte-selecteur-label" id="{{ $id }}-label">
        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i> {{ $label }}
    </span>

    <div class="carte-selecteur-recherche">
        <input type="text" id="{{ $id }}-recherche" class="form-control"
               placeholder="Rechercher une ville, une adresse… (ex. Tunis, Sousse, Sfax)"
               aria-label="Rechercher un lieu sur la carte" autocomplete="off">
        <button type="button" class="btn btn-outline-secondary" id="{{ $id }}-btn-recherche">
            <i class="bi bi-search" aria-hidden="true"></i> Rechercher
        </button>
        <button type="button" class="btn btn-outline-success" id="{{ $id }}-btn-position" title="Utiliser ma position actuelle">
            <i class="bi bi-crosshair" aria-hidden="true"></i> Ma position
        </button>
    </div>

    <div id="{{ $id }}-map" class="carte-selecteur-map" role="application"
         aria-label="Carte interactive : cliquez pour placer le marqueur"></div>

    <div class="carte-selecteur-bas">
        <span id="{{ $id }}-statut" class="carte-selecteur-statut" aria-live="polite">
            <i class="bi bi-cursor" aria-hidden="true"></i>
            @if (filled($latValeur) && filled($lngValeur))
                Position enregistrée — déplacez le marqueur si besoin.
            @else
                Cliquez sur la carte pour placer le marqueur.
            @endif
        </span>
        <span class="carte-selecteur-coords">
            <span id="{{ $id }}-coords">
                @if (filled($latValeur) && filled($lngValeur))
                    {{ number_format((float) $latValeur, 6, '.', '') }}, {{ number_format((float) $lngValeur, 6, '.', '') }}
                @else
                    Aucune position
                @endif
            </span>
            <button type="button" class="btn btn-link btn-sm carte-selecteur-effacer" id="{{ $id }}-btn-effacer"
                    @if (!filled($latValeur) || !filled($lngValeur)) hidden @endif>
                Effacer
            </button>
        </span>
    </div>

    {{-- Coordonnées envoyées au serveur (remplies par la carte, plus de saisie manuelle) --}}
    <input type="hidden" name="latitude" id="{{ $id }}-latitude" value="{{ $latValeur }}">
    <input type="hidden" name="longitude" id="{{ $id }}-longitude" value="{{ $lngValeur }}">

    @error('latitude')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('longitude')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
</div>

@pushOnce('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .carte-selecteur { margin-bottom: 1rem; }
        .carte-selecteur-label { display: block; font-weight: 600; margin-bottom: .5rem; }
        .carte-selecteur-label .bi { color: #198754; }
        .carte-selecteur-recherche { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .5rem; }
        .carte-selecteur-recherche .form-control { flex: 1 1 220px; width: auto; }
        .carte-selecteur-map {
            height: 320px; width: 100%; z-index: 1;
            border: 1px solid #ced4da; border-radius: .5rem;
        }
        .carte-selecteur-bas {
            display: flex; flex-wrap: wrap; gap: .5rem;
            justify-content: space-between; align-items: center; margin-top: .5rem;
        }
        .carte-selecteur-statut { font-size: .875rem; color: #6c757d; }
        .carte-selecteur-coords { font-size: .875rem; font-variant-numeric: tabular-nums; color: #495057; }
        .carte-selecteur-effacer { padding: 0 0 0 .5rem; font-size: .875rem; }
    </style>
@endPushOnce

@pushOnce('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endPushOnce

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var racine = document.querySelector('[data-carte-selecteur="{{ $id }}"]');
            if (!racine || typeof L === 'undefined') return;

            var latInput = document.getElementById('{{ $id }}-latitude');
            var lngInput = document.getElementById('{{ $id }}-longitude');
            var rechercheInput = document.getElementById('{{ $id }}-recherche');
            var statut = document.getElementById('{{ $id }}-statut');
            var coords = document.getElementById('{{ $id }}-coords');
            var btnEffacer = document.getElementById('{{ $id }}-btn-effacer');

            var lat0 = parseFloat(latInput.value);
            var lng0 = parseFloat(lngInput.value);
            var aPosition = !isNaN(lat0) && !isNaN(lng0);

            var map = L.map('{{ $id }}-map').setView(aPosition ? [lat0, lng0] : [36.8065, 10.1815], aPosition ? 13 : 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);

            var marqueur = null;

            function afficherCoords() {
                if (latInput.value && lngInput.value) {
                    coords.textContent = Number(latInput.value).toFixed(6) + ', ' + Number(lngInput.value).toFixed(6);
                    btnEffacer.hidden = false;
                } else {
                    coords.textContent = 'Aucune position';
                    btnEffacer.hidden = true;
                }
            }

            function placer(lat, lng, zoom) {
                latInput.value = Number(lat).toFixed(8);
                lngInput.value = Number(lng).toFixed(8);
                if (marqueur) {
                    marqueur.setLatLng([lat, lng]);
                } else {
                    marqueur = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marqueur.on('dragend', function (e) {
                        var p = e.target.getLatLng();
                        placer(p.lat, p.lng);
                        geocoderInverse(p.lat, p.lng);
                    });
                }
                if (zoom) map.setView([lat, lng], zoom);
                afficherCoords();
            }

            function formaterAdresse(data) {
                if (!data) return '';
                var a = data.address || {};
                var morceaux = [data.name, a.road || a.pedestrian || a.suburb, a.city || a.town || a.village || a.county, a.country]
                    .filter(function (v, i, t) { return v && t.indexOf(v) === i; });
                var texte = morceaux.join(', ');
                return texte.length >= 3 ? texte : (data.display_name || '');
            }

            function geocoderInverse(lat, lng) {
                statut.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Recherche de l\u2019adresse…';
                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng, {
                    headers: { 'Accept-Language': 'fr,ar,en' }
                }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
                    var texte = formaterAdresse(data);
                    if (texte) {
                        statut.innerHTML = '<i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> ' + texte.replace(/</g, '&lt;');
                        if (marqueur) marqueur.bindPopup(texte.replace(/</g, '&lt;')).openPopup();
                    } else {
                        statut.textContent = 'Position placée sur la carte.';
                    }
                }).catch(function () {
                    statut.textContent = 'Position placée sur la carte.';
                });
            }

            function rechercher() {
                var q = (rechercheInput.value || '').trim();
                if (!q) return;
                statut.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Recherche de « ' + q.replace(/</g, '&lt;') + ' »…';
                fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(q), {
                    headers: { 'Accept-Language': 'fr,ar,en' }
                }).then(function (r) { return r.ok ? r.json() : []; }).then(function (resultats) {
                    if (resultats && resultats.length) {
                        var lat = parseFloat(resultats[0].lat), lng = parseFloat(resultats[0].lon);
                        placer(lat, lng, 13);
                        var texte = formaterAdresse(resultats[0]);
                        statut.innerHTML = texte
                            ? '<i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> ' + texte.replace(/</g, '&lt;')
                            : 'Lieu trouvé.';
                        if (marqueur && texte) marqueur.bindPopup(texte.replace(/</g, '&lt;')).openPopup();
                    } else {
                        statut.innerHTML = '<i class="bi bi-exclamation-triangle text-warning" aria-hidden="true"></i> Aucun résultat pour « ' + q.replace(/</g, '&lt;') + ' »';
                    }
                }).catch(function () {
                    statut.textContent = 'Recherche impossible (connexion). Cliquez directement sur la carte.';
                });
            }

            map.on('click', function (e) {
                placer(e.latlng.lat, e.latlng.lng);
                geocoderInverse(e.latlng.lat, e.latlng.lng);
            });

            document.getElementById('{{ $id }}-btn-recherche').addEventListener('click', rechercher);
            rechercheInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); rechercher(); }
            });

            document.getElementById('{{ $id }}-btn-position').addEventListener('click', function () {
                if (!navigator.geolocation) {
                    statut.textContent = 'Géolocalisation non disponible sur ce navigateur.';
                    return;
                }
                statut.textContent = 'Localisation en cours…';
                navigator.geolocation.getCurrentPosition(function (pos) {
                    placer(pos.coords.latitude, pos.coords.longitude, 14);
                    geocoderInverse(pos.coords.latitude, pos.coords.longitude);
                }, function () {
                    statut.textContent = 'Position refusée : cliquez directement sur la carte.';
                }, { timeout: 10000 });
            });

            btnEffacer.addEventListener('click', function () {
                latInput.value = '';
                lngInput.value = '';
                if (marqueur) { map.removeLayer(marqueur); marqueur = null; }
                statut.textContent = 'Position effacée. Cliquez sur la carte pour placer le marqueur.';
                afficherCoords();
            });

            if (aPosition) placer(lat0, lng0);
            afficherCoords();
            setTimeout(function () { map.invalidateSize(); }, 250);
        });
    </script>
@endpush
