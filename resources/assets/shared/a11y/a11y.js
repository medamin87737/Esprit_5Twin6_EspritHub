/**
 * NutriTrace — moteur d'accessibilité (préférences + panneau + lecture vocale)
 * Sans framework externe. Persistance : localStorage.
 */
import './a11y.css';

(function () {
    'use strict';

    const STORAGE_KEY = 'nt-a11y-prefs-v1';
    const ROOT = document.documentElement;

    const DEFAULTS = {
        blueFilter: 0,
        theme: 'default',
        brightness: 100,
        reduceMotion: false,
        focusMode: false,
        textScale: 100,
        uiZoom: 100,
        font: 'default',
        letterSpacing: 0,
        wordSpacing: 0,
        lineHeight: 0,
        paragraphSpacing: 0,
        highContrast: false,
        underlineLinks: false,
        colorPatterns: true,
        bigCursor: false,
        strongFocus: true,
        simplifiedReading: false,
        shortcuts: false,
    };

    const TEXT_STEPS = [80, 90, 100, 110, 125, 150, 175, 200];
    const ZOOM_STEPS = [80, 90, 100, 110, 125, 150, 200];

    let prefs = load();
    let lastFocus = null;
    let speechUtterance = null;

    function load() {
        let chargees;
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            chargees = !raw ? { ...DEFAULTS } : { ...DEFAULTS, ...JSON.parse(raw) };
        } catch (e) {
            chargees = { ...DEFAULTS };
        }
        // Réglages retirés du panneau : neutralisés même s'ils avaient été activés avant.
        // Seuls les modes Clair / Sombre sont conservés.
        if (chargees.theme !== 'dark' && chargees.theme !== 'light') {
            chargees.theme = 'default';
        }
        chargees.uiZoom = 100;
        chargees.reduceMotion = false;
        chargees.focusMode = false;
        chargees.strongFocus = true;
        chargees.simplifiedReading = false;
        chargees.shortcuts = false;

        return chargees;
    }

    function save() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        } catch (e) {
            /* stockage indisponible : réglages session uniquement */
        }
    }

    function setCssVar(name, value) {
        ROOT.style.setProperty(name, value);
    }

    function setData(name, value) {
        if (value === false || value === null || value === '' || value === 'default' || value === 0 || value === 'off') {
            ROOT.removeAttribute(name);
            return;
        }
        ROOT.setAttribute(name, String(value));
    }

    function apply() {
        const blue = Math.max(0, Math.min(4, Number(prefs.blueFilter) || 0));
        setCssVar('--nt-a11y-blue', String(blue * 0.08));
        setData('data-nt-a11y-blue', blue > 0 ? blue : null);

        const theme = prefs.highContrast ? 'high' : prefs.theme;
        if (theme === 'default') {
            ROOT.removeAttribute('data-nt-a11y-theme');
            ROOT.removeAttribute('data-nt-a11y-contrast');
        } else if (theme === 'high' || prefs.highContrast) {
            ROOT.setAttribute('data-nt-a11y-contrast', 'high');
            ROOT.removeAttribute('data-nt-a11y-theme');
        } else {
            ROOT.setAttribute('data-nt-a11y-theme', theme);
            ROOT.removeAttribute('data-nt-a11y-contrast');
        }

        const bright = Math.max(70, Math.min(100, Number(prefs.brightness) || 100));
        setCssVar('--nt-a11y-brightness-pct', String(bright));
        setData('data-nt-a11y-brightness', bright === 100 ? null : bright);

        if (prefs.reduceMotion) {
            ROOT.setAttribute('data-nt-a11y-motion', 'reduce');
        } else {
            ROOT.removeAttribute('data-nt-a11y-motion');
        }

        setData('data-nt-a11y-focus', prefs.focusMode ? 'on' : null);

        const textScale = Math.max(80, Math.min(200, Number(prefs.textScale) || 100));
        setCssVar('--nt-a11y-text-scale', String(textScale / 100));
        setData('data-nt-a11y-text', textScale === 100 ? null : textScale);

        const zoom = Math.max(80, Math.min(200, Number(prefs.uiZoom) || 100));
        setCssVar('--nt-a11y-ui-zoom', String(zoom / 100));
        setData('data-nt-a11y-zoom', zoom === 100 ? null : zoom);

        if (prefs.font && prefs.font !== 'default') {
            ROOT.setAttribute('data-nt-a11y-font', prefs.font);
        } else {
            ROOT.removeAttribute('data-nt-a11y-font');
        }

        const letter = Number(prefs.letterSpacing) || 0;
        const word = Number(prefs.wordSpacing) || 0;
        const line = Number(prefs.lineHeight) || 0;
        const para = Number(prefs.paragraphSpacing) || 0;
        setCssVar('--nt-a11y-letter', `${letter * 0.04}em`);
        setCssVar('--nt-a11y-word', `${word * 0.08}em`);
        setCssVar('--nt-a11y-line', String(line * 0.15));
        setCssVar('--nt-a11y-para', `${para * 0.35}rem`);
        setData('data-nt-a11y-letter', letter || null);
        setData('data-nt-a11y-word', word || null);
        setData('data-nt-a11y-line', line || null);
        setData('data-nt-a11y-para', para || null);

        setData('data-nt-a11y-links', prefs.underlineLinks ? 'on' : null);
        setData('data-nt-a11y-patterns', prefs.colorPatterns ? 'on' : null);
        setData('data-nt-a11y-cursor', prefs.bigCursor ? 'on' : null);
        setData('data-nt-a11y-focusring', prefs.strongFocus ? 'on' : null);
        setData('data-nt-a11y-reading', prefs.simplifiedReading ? 'on' : null);

        syncPanelUI();
        updateSummary();
    }

    function nearest(list, value) {
        return list.reduce((best, cur) =>
            Math.abs(cur - value) < Math.abs(best - value) ? cur : best
        );
    }

    function stepValue(list, current, dir) {
        const idx = list.indexOf(nearest(list, current));
        const next = list[Math.max(0, Math.min(list.length - 1, idx + dir))];
        return next;
    }

    function update(key, value) {
        prefs = { ...prefs, [key]: value };
        save();
        apply();
    }

    function resetAll() {
        prefs = { ...DEFAULTS };
        save();
        apply();
    }

    function countActive() {
        let n = 0;
        if (prefs.blueFilter > 0) n++;
        if (prefs.theme === 'dark') n++;
        if (prefs.brightness !== 100) n++;
        if (prefs.textScale !== 100) n++;
        if (prefs.font !== 'default') n++;
        if (prefs.letterSpacing || prefs.wordSpacing || prefs.lineHeight || prefs.paragraphSpacing) n++;
        if (prefs.highContrast) n++;
        if (prefs.underlineLinks) n++;
        if (prefs.bigCursor) n++;
        if (!prefs.colorPatterns) n++;
        return n;
    }

    function updateSummary() {
        const el = document.getElementById('nt-a11y-summary');
        if (!el) return;
        const n = countActive();
        el.textContent = n === 0
            ? 'Aucun réglage personnalisé actif.'
            : `${n} réglage${n > 1 ? 's' : ''} d’accessibilité actif${n > 1 ? 's' : ''}.`;
    }

    function syncPanelUI() {
        const panel = document.getElementById('nt-a11y-panel');
        if (!panel) return;

        const bind = (sel, prop, transform) => {
            const el = panel.querySelector(sel);
            if (!el) return;
            const val = transform ? transform(prefs[prop]) : prefs[prop];
            if (el.type === 'checkbox') el.checked = !!val;
            else if (el.type === 'range' || el.tagName === 'SELECT' || el.tagName === 'INPUT') el.value = val;
        };

        bind('[data-a11y="blueFilter"]', 'blueFilter');
        bind('[data-a11y="brightness"]', 'brightness');
        bind('[data-a11y="reduceMotion"]', 'reduceMotion');
        bind('[data-a11y="focusMode"]', 'focusMode');
        bind('[data-a11y="textScale"]', 'textScale');
        bind('[data-a11y="font"]', 'font');
        bind('[data-a11y="letterSpacing"]', 'letterSpacing');
        bind('[data-a11y="wordSpacing"]', 'wordSpacing');
        bind('[data-a11y="lineHeight"]', 'lineHeight');
        bind('[data-a11y="paragraphSpacing"]', 'paragraphSpacing');
        bind('[data-a11y="highContrast"]', 'highContrast');
        bind('[data-a11y="underlineLinks"]', 'underlineLinks');
        bind('[data-a11y="colorPatterns"]', 'colorPatterns');
        bind('[data-a11y="bigCursor"]', 'bigCursor');
        bind('[data-a11y="strongFocus"]', 'strongFocus');
        bind('[data-a11y="simplifiedReading"]', 'simplifiedReading');
        bind('[data-a11y="shortcuts"]', 'shortcuts');

        const setLabel = (key, text) => {
            panel.querySelectorAll(`[data-a11y-label="${key}"]`).forEach((el) => {
                el.textContent = text;
            });
        };
        setLabel('textScale', `${prefs.textScale} %`);
        setLabel('uiZoom', `${prefs.uiZoom} %`);
        setLabel('blueFilter', prefs.blueFilter === 0 ? 'Off' : `N${prefs.blueFilter}`);
        setLabel('brightness', `${prefs.brightness}%`);
        setLabel('letterSpacing', String(prefs.letterSpacing));
        setLabel('wordSpacing', String(prefs.wordSpacing));
        setLabel('lineHeight', String(prefs.lineHeight));
        setLabel('paragraphSpacing', String(prefs.paragraphSpacing));

        panel.querySelectorAll('[data-a11y-zoom-btn]').forEach((btn) => {
            const on = Number(btn.dataset.a11yZoomBtn) === prefs.uiZoom;
            btn.setAttribute('aria-pressed', String(on));
            btn.classList.toggle('is-active', on);
        });

        const theme = prefs.theme || 'default';
        const isDark = theme === 'dark' || theme === 'night';
        const darkToggle = panel.querySelector('[data-a11y-dark-toggle]');
        if (darkToggle) darkToggle.checked = isDark;

        panel.querySelectorAll('[data-a11y-theme-btn]').forEach((btn) => {
            const val = btn.dataset.a11yThemeBtn;
            let on = val === theme;
            if (btn.classList.contains('nt-a11y-mode__btn--light')) {
                on = theme === 'light' || theme === 'soft' || theme === 'default';
            }
            if (btn.classList.contains('nt-a11y-mode__btn--dark')) {
                on = isDark;
            }
            btn.setAttribute('aria-pressed', String(on));
            btn.classList.toggle('is-active', on);
        });

        paintAllGauges(panel);
    }

    function paintGauge(el) {
        if (!el || el.type !== 'range') return;
        const min = Number(el.min) || 0;
        const max = Number(el.max) || 100;
        const val = Number(el.value);
        const pct = max === min ? 0 : ((val - min) / (max - min)) * 100;

        const pot = el.closest('[data-a11y-pot]');
        if (pot) {
            pot.style.setProperty('--nt-pot-pct', `${pct}%`);
            const fill = pot.querySelector('.nt-a11y-pot__fill');
            if (fill) fill.style.width = `${pct}%`;
            const thumb = pot.querySelector('.nt-a11y-pot__thumb');
            if (thumb) thumb.style.left = `${pct}%`;
        }

        const dial = el.closest('[data-a11y-dial]');
        if (dial) {
            dial.style.setProperty('--nt-dial-pct', String(pct));
            const progress = dial.querySelector('.nt-a11y-dial__progress');
            const C = 2 * Math.PI * 30;
            if (progress) {
                progress.style.strokeDasharray = String(C);
                progress.style.strokeDashoffset = String(C - (C * pct) / 100);
            }
            const knob = dial.querySelector('.nt-a11y-dial__knob');
            if (knob) {
                const angle = (pct / 100) * 360;
                knob.style.transform = `rotate(${angle}deg) translateY(-1.72rem)`;
            }
        }
    }

    function paintAllGauges(root) {
        (root || document).querySelectorAll('[data-a11y-pot] .nt-a11y-pot__input, [data-a11y-dial] .nt-a11y-dial__input, input[type="range"][data-a11y]').forEach(paintGauge);
    }

    /* —— Panneau UI —— */
    function getFocusable(container) {
        return [...container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )].filter((el) => !el.hasAttribute('hidden') && el.offsetParent !== null);
    }

    function openPanel() {
        const panel = document.getElementById('nt-a11y-panel');
        const backdrop = document.getElementById('nt-a11y-backdrop');
        const fab = document.getElementById('nt-a11y-fab');
        if (!panel) return;

        lastFocus = document.activeElement;
        panel.hidden = false;
        if (backdrop) backdrop.hidden = false;
        requestAnimationFrame(() => panel.classList.add('is-open'));
        fab?.setAttribute('aria-expanded', 'true');

        const focusables = getFocusable(panel);
        (focusables[0] || panel).focus();
    }

    function closePanel() {
        const panel = document.getElementById('nt-a11y-panel');
        const backdrop = document.getElementById('nt-a11y-backdrop');
        const fab = document.getElementById('nt-a11y-fab');
        if (!panel) return;

        panel.classList.remove('is-open');
        fab?.setAttribute('aria-expanded', 'false');
        setTimeout(() => {
            panel.hidden = true;
            if (backdrop) backdrop.hidden = true;
            (lastFocus || fab)?.focus?.();
        }, 200);
    }

    function togglePanel() {
        const panel = document.getElementById('nt-a11y-panel');
        if (!panel || panel.hidden) openPanel();
        else closePanel();
    }

    /* —— Lecture vocale —— */
    function speechSupported() {
        return typeof window.speechSynthesis !== 'undefined' && typeof window.SpeechSynthesisUtterance !== 'undefined';
    }

    function setSpeechState(state) {
        const box = document.querySelector('.nt-a11y-speech');
        if (box) box.dataset.state = state;
        const status = document.getElementById('nt-a11y-speech-status');
        if (!status) return;
        const map = {
            idle: 'Lecture arrêtée.',
            speaking: 'Lecture en cours…',
            paused: 'Lecture en pause.',
            unsupported: 'Lecture vocale non disponible sur ce navigateur.',
        };
        status.textContent = map[state] || map.idle;
    }

    function getSpeechText() {
        const sel = window.getSelection?.()?.toString()?.trim();
        if (sel) return sel;
        const main = document.getElementById('contenu-principal') || document.querySelector('main');
        return (main?.innerText || document.body.innerText || '').trim().slice(0, 8000);
    }

    function speechStart() {
        if (!speechSupported()) {
            setSpeechState('unsupported');
            return;
        }
        window.speechSynthesis.cancel();
        const text = getSpeechText();
        if (!text) {
            setSpeechState('idle');
            return;
        }
        speechUtterance = new SpeechSynthesisUtterance(text);
        speechUtterance.lang = document.documentElement.lang || 'fr-FR';
        speechUtterance.onend = () => setSpeechState('idle');
        speechUtterance.onerror = () => setSpeechState('idle');
        window.speechSynthesis.speak(speechUtterance);
        setSpeechState('speaking');
    }

    function speechPause() {
        if (!speechSupported()) return;
        if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
            window.speechSynthesis.pause();
            setSpeechState('paused');
        } else if (window.speechSynthesis.paused) {
            window.speechSynthesis.resume();
            setSpeechState('speaking');
        }
    }

    function speechStop() {
        if (!speechSupported()) return;
        window.speechSynthesis.cancel();
        setSpeechState('idle');
    }

    /* —— Raccourcis —— */
    function isTypingTarget(el) {
        if (!el) return false;
        const tag = el.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    function onKeydown(e) {
        if (e.key === 'Escape') {
            const panel = document.getElementById('nt-a11y-panel');
            if (panel && !panel.hidden) {
                e.preventDefault();
                closePanel();
                return;
            }
        }

        const panel = document.getElementById('nt-a11y-panel');
        if (panel && !panel.hidden && e.key === 'Tab') {
            const focusables = getFocusable(panel);
            if (!focusables.length) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }

        if (!prefs.shortcuts || isTypingTarget(e.target)) return;
        if (!(e.altKey && !e.ctrlKey && !e.metaKey)) return;

        const key = e.key.toLowerCase();
        if (key === 'a') {
            e.preventDefault();
            togglePanel();
        } else if (key === '1') {
            e.preventDefault();
            document.getElementById('contenu-principal')?.focus?.();
            document.getElementById('contenu-principal')?.scrollIntoView({ behavior: prefs.reduceMotion ? 'auto' : 'smooth' });
        } else if (key === '2') {
            e.preventDefault();
            const nav = document.getElementById('mainNav')
                || document.getElementById('accordionSidebar')
                || document.getElementById('navigation-principale')
                || document.querySelector('nav');
            const target = nav?.querySelector?.('a, button') || nav;
            target?.focus?.();
            nav?.scrollIntoView({ behavior: prefs.reduceMotion ? 'auto' : 'smooth' });
        } else if (key === '3') {
            e.preventDefault();
            const search = document.getElementById('table-search')
                || document.getElementById('numero')
                || document.querySelector('input[type="search"], input[name="q"]');
            search?.focus?.();
        }
    }

    function bindPanel() {
        const fab = document.getElementById('nt-a11y-fab');
        const panel = document.getElementById('nt-a11y-panel');
        const backdrop = document.getElementById('nt-a11y-backdrop');
        const closeBtn = document.getElementById('nt-a11y-close');
        const doneBtn = document.getElementById('nt-a11y-done');
        const resetBtn = document.getElementById('nt-a11y-reset');

        fab?.addEventListener('click', togglePanel);
        closeBtn?.addEventListener('click', closePanel);
        doneBtn?.addEventListener('click', closePanel);
        backdrop?.addEventListener('click', closePanel);
        resetBtn?.addEventListener('click', () => {
            resetAll();
        });

        panel?.querySelectorAll('.nt-a11y-cat__btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                const id = btn.getAttribute('aria-controls');
                const body = id ? document.getElementById(id) : null;
                btn.setAttribute('aria-expanded', String(!expanded));
                if (body) body.hidden = expanded;
            });
        });

        panel?.querySelectorAll('[data-a11y]').forEach((el) => {
            const key = el.getAttribute('data-a11y');
            if (!key) return;
            const handler = () => {
                paintGauge(el);
                if (el.type === 'checkbox') update(key, el.checked);
                else if (el.type === 'range' || el.type === 'number') update(key, Number(el.value));
                else update(key, el.value);
            };
            el.addEventListener('input', handler);
            el.addEventListener('change', handler);
        });
        paintAllGauges(panel);

        panel?.querySelector('[data-a11y-action="text-down"]')?.addEventListener('click', () => {
            update('textScale', stepValue(TEXT_STEPS, prefs.textScale, -1));
        });
        panel?.querySelector('[data-a11y-action="text-up"]')?.addEventListener('click', () => {
            update('textScale', stepValue(TEXT_STEPS, prefs.textScale, 1));
        });
        panel?.querySelector('[data-a11y-action="text-reset"]')?.addEventListener('click', () => {
            update('textScale', 100);
        });

        panel?.querySelectorAll('[data-a11y-zoom-btn]').forEach((btn) => {
            btn.addEventListener('click', () => update('uiZoom', Number(btn.dataset.a11yZoomBtn)));
        });

        panel?.querySelectorAll('[data-a11y-theme-btn]').forEach((btn) => {
            btn.addEventListener('click', () => update('theme', btn.dataset.a11yThemeBtn || 'default'));
        });

        panel?.querySelector('[data-a11y-dark-toggle]')?.addEventListener('change', (e) => {
            update('theme', e.target.checked ? 'dark' : 'light');
        });

        panel?.querySelector('[data-speech="start"]')?.addEventListener('click', speechStart);
        panel?.querySelector('[data-speech="pause"]')?.addEventListener('click', speechPause);
        panel?.querySelector('[data-speech="stop"]')?.addEventListener('click', speechStop);

        if (!speechSupported()) setSpeechState('unsupported');
        else setSpeechState('idle');

        document.addEventListener('keydown', onKeydown);
    }

    function boot() {
        apply();
        bindPanel();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.NtA11y = {
        getPrefs: () => ({ ...prefs }),
        reset: resetAll,
        open: openPanel,
        close: closePanel,
        apply,
    };
})();
