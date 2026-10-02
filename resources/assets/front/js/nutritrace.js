import { Toast } from 'bootstrap';

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
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
