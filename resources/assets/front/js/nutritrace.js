import { Toast } from 'bootstrap';
import { initCartes } from './carte.js';
import { initScan } from './scan.js';

document.documentElement.classList.add('reveal-ready');

const init = () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal, .eco-bars').forEach((el) => observer.observe(el));

    const heroVideo = document.querySelector('.masthead-video');
    if (heroVideo && reduceMotion) {
        heroVideo.pause();
        heroVideo.removeAttribute('autoplay');
    }

    document.querySelectorAll('[data-year]').forEach((el) => {
        el.textContent = new Date().getFullYear();
    });

    document.querySelectorAll('.flash-toast').forEach((el) => {
        Toast.getOrCreateInstance(el).show();
    });

    initScan();
    initCartes();

    // Indicateur : l'unité suit le type choisi (eau → L, énergie → kWh…).
    document.querySelectorAll('[data-unites]').forEach((bloc) => {
        const unites = JSON.parse(bloc.dataset.unites);
        const type = bloc.querySelector('[data-type-indicateur]');
        const unite = bloc.querySelector('[data-unite-indicateur]');
        type?.addEventListener('change', () => {
            if (unite && unites[type.value]) {
                unite.value = unites[type.value];
            }
        });
    });

    // Comparaison : nombre maximal de produits cochés (le serveur applique aussi la limite).
    document.querySelectorAll('[data-max-selection]').forEach((form) => {
        const max = Number(form.dataset.maxSelection);
        const cases = form.querySelectorAll('input[type="checkbox"][name="produits[]"]');
        const synchroniser = () => {
            const coches = [...cases].filter((c) => c.checked).length;
            cases.forEach((c) => { c.disabled = !c.checked && coches >= max; });
        };
        cases.forEach((c) => c.addEventListener('change', synchroniser));
        synchroniser();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
