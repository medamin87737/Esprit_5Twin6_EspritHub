// Cartes GPS (parcours d'un lot, annuaire) : Leaflet n'est chargé que si la page contient une carte.
export async function initCartes() {
    const conteneurs = document.querySelectorAll('[data-carte]');
    if (!conteneurs.length) {
        return;
    }

    const { default: L } = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    conteneurs.forEach((el) => {
        const points = JSON.parse(el.dataset.carte || '[]').filter((p) => p.lat !== null && p.lng !== null);
        if (!points.length) {
            return;
        }

        const carte = L.map(el, { scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; contributeurs OpenStreetMap',
        }).addTo(carte);

        const trace = el.dataset.trace === '1';
        const coordonnees = points.map((p) => [p.lat, p.lng]);

        points.forEach((p, index) => {
            const icone = L.divIcon({
                className: 'map-marker',
                html: `<span>${trace ? index + 1 : ''}</span>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15],
            });

            const contenu = document.createElement('div');
            const titre = document.createElement('strong');
            titre.textContent = p.titre;
            contenu.appendChild(titre);
            if (p.texte) {
                const texte = document.createElement('div');
                texte.className = 'small text-muted';
                texte.textContent = p.texte;
                contenu.appendChild(texte);
            }

            L.marker([p.lat, p.lng], { icon: icone, title: p.titre }).bindPopup(contenu).addTo(carte);
        });

        if (trace && coordonnees.length > 1) {
            L.polyline(coordonnees, { color: '#087443', weight: 3, dashArray: '6 6' }).addTo(carte);
        }

        if (coordonnees.length === 1) {
            carte.setView(coordonnees[0], 10);
        } else {
            carte.fitBounds(coordonnees, { padding: [40, 40] });
        }
    });
}
