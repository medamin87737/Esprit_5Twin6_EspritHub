import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Filler, LinearScale, LineController, LineElement, PointElement, Tooltip);

const COULEURS = {
    foret: '#064E35',
    vert: '#087443',
    gris: '#B8C4BC',
    grille: '#EEF2EF',
    texte: '#17231C',
    secondaire: '#65756A',
};

const nombre = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
const reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Chart.defaults.font.family = '"Inter", system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = COULEURS.secondaire;
Chart.defaults.animation = reduit ? false : { duration: 350, easing: 'easeOutCubic' };
Object.assign(Chart.defaults.plugins.tooltip, {
    backgroundColor: COULEURS.texte,
    titleColor: '#fff',
    bodyColor: '#E1E9E3',
    titleFont: { weight: '600' },
    padding: 10,
    cornerRadius: 8,
    boxPadding: 4,
    usePointStyle: true,
});

const axeX = { grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } };
const axeY = { beginAtZero: true, border: { display: false }, grid: { color: COULEURS.grille }, ticks: { maxTicksLimit: 5 } };

function degrade(canvas, rgb) {
    const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, canvas.parentElement.clientHeight || 300);
    gradient.addColorStop(0, `rgba(${rgb}, 0.16)`);
    gradient.addColorStop(1, `rgba(${rgb}, 0)`);
    return gradient;
}

function rayonPoint(longueur) {
    if (longueur === 1) {
        return 5;
    }
    return longueur > 40 ? 0 : 2.5;
}

/** Crée un graphique sans casser la page si Chart.js échoue : un message remplace le canevas. */
function dessiner(id, creer) {
    const canvas = document.getElementById(id);
    if (!canvas) {
        return null;
    }
    const conteneur = canvas.parentElement;
    try {
        const graphique = creer(canvas);
        conteneur.classList.add('is-ready');
        return graphique;
    } catch (erreur) {
        console.error(erreur);
        conteneur.classList.add('is-ready', 'is-error');
        canvas.hidden = true;
        const message = document.createElement('p');
        message.className = 'nt-chart-empty';
        message.textContent = 'Impossible de charger les données de ce graphique.';
        conteneur.append(message);
        return null;
    }
}

function tendance(donnees) {
    const select = document.querySelector('[data-metrique]');
    const total = document.querySelector('[data-tendance-total]');
    const legende = document.querySelector('[data-tendance-legende]');
    const vide = document.querySelector('#chart-tendance + [data-chart-empty]');
    const metrique = () => donnees.tendances[select.value];

    const graphique = dessiner('chart-tendance', (canvas) => new Chart(canvas, {
        type: 'line',
        data: {
            labels: donnees.libelles,
            datasets: [
                {
                    label: 'Période actuelle',
                    data: metrique().courant,
                    borderColor: COULEURS.vert,
                    backgroundColor: degrade(canvas, '8, 116, 67'),
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: rayonPoint(donnees.libelles.length),
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: COULEURS.vert,
                },
                {
                    label: 'Période précédente',
                    data: metrique().precedent,
                    borderColor: COULEURS.gris,
                    borderDash: [5, 4],
                    borderWidth: 1.5,
                    fill: false,
                    tension: 0.35,
                    pointRadius: donnees.libelles.length === 1 ? 4 : 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: COULEURS.gris,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: { x: axeX, y: { ...axeY, ticks: { ...axeY.ticks, precision: 0 } } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => donnees.libellesLongs[items[0].dataIndex] ?? '',
                        label: (item) => item.datasetIndex === 0
                            ? ` Période actuelle : ${nombre.format(item.parsed.y)}`
                            : ` ${donnees.libellesPrecedents[item.dataIndex] ?? 'Période précédente'} : ${nombre.format(item.parsed.y)}`,
                    },
                },
            },
        },
    }));

    const actualiser = () => {
        const m = metrique();
        total.textContent = nombre.format(m.total);
        legende.textContent = `${m.legende} sur la période`;
        vide.hidden = m.total > 0 || m.totalPrecedent > 0;
        document.getElementById('chart-tendance').setAttribute(
            'aria-label',
            `${m.libelle} : ${m.total} sur la période, contre ${m.totalPrecedent} la période précédente`,
        );
        if (graphique) {
            graphique.data.datasets[0].data = m.courant;
            graphique.data.datasets[1].data = m.precedent;
            graphique.update();
        }
    };

    select?.addEventListener('change', actualiser);
    actualiser();
}

function repartition({ segments, total }) {
    dessiner('chart-repartition', (canvas) => new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: segments.map((s) => s.libelle),
            datasets: [{
                data: segments.map((s) => s.total),
                backgroundColor: segments.map((s) => s.couleur),
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => ` ${item.label} : ${item.parsed} lot${item.parsed > 1 ? 's' : ''} (${Math.round((item.parsed / total) * 100)} %)`,
                    },
                },
            },
        },
    }));
}

function certifications({ types, statuts }) {
    dessiner('chart-certifications', (canvas) => new Chart(canvas, {
        type: 'bar',
        data: {
            labels: types,
            datasets: statuts.map((s) => ({
                label: s.libelle,
                data: s.valeurs,
                backgroundColor: s.couleur,
                borderRadius: 4,
                borderSkipped: false,
                barThickness: 18,
            })),
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false, axis: 'y' },
            scales: {
                x: { ...axeY, stacked: true, ticks: { ...axeY.ticks, precision: 0 } },
                y: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { color: COULEURS.texte, font: { weight: '600' } } },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    filter: (item) => item.parsed.x > 0,
                    callbacks: { label: (item) => ` ${item.dataset.label} : ${item.parsed.x}` },
                },
            },
        },
    }));
}

function co2(donnees) {
    dessiner('chart-co2', (canvas) => new Chart(canvas, {
        type: 'line',
        data: {
            labels: donnees.libelles,
            datasets: [{
                label: 'Empreinte moyenne',
                data: donnees.co2,
                borderColor: COULEURS.foret,
                backgroundColor: degrade(canvas, '6, 78, 53'),
                fill: true,
                spanGaps: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: donnees.co2.filter((v) => v !== null).length <= 1 ? 4 : 0,
                pointHoverRadius: 4,
                pointBackgroundColor: COULEURS.foret,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: { x: axeX, y: { ...axeY, ticks: { ...axeY.ticks, callback: (v) => `${nombre.format(v)} kg` } } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => donnees.libellesLongs[items[0].dataIndex] ?? '',
                        label: (item) => ` ${nombre.format(item.parsed.y)} kg CO₂e par unité`,
                    },
                },
            },
        },
    }));
}

function periode() {
    const formulaire = document.querySelector('[data-periode-form]');
    if (!formulaire) {
        return;
    }
    const select = formulaire.querySelector('select[name="periode"]');
    const dates = formulaire.querySelector('[data-dates]');
    const synchroniser = () => {
        const perso = select.value === 'perso';
        dates.hidden = !perso;
        dates.querySelectorAll('input').forEach((champ) => {
            champ.disabled = !perso;
            champ.required = perso;
        });
    };
    select.addEventListener('change', synchroniser);
    synchroniser();
}

periode();

const source = document.getElementById('nt-dashboard-data');
if (source) {
    const donnees = JSON.parse(source.textContent);
    tendance(donnees);
    repartition(donnees.repartition);
    certifications(donnees.certifications);
    co2(donnees);
}
