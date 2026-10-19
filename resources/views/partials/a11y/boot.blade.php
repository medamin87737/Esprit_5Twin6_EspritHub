{{-- Applique les préférences avant le premier rendu pour limiter le flash --}}
<script>
(function () {
    try {
        var raw = localStorage.getItem('nt-a11y-prefs-v1');
        if (!raw) return;
        var p = JSON.parse(raw);
        var h = document.documentElement;
        var s = h.style;
        if (p.blueFilter > 0) {
            s.setProperty('--nt-a11y-blue', String(Number(p.blueFilter) * 0.08));
            h.setAttribute('data-nt-a11y-blue', String(p.blueFilter));
        }
        if (p.textScale && p.textScale !== 100) s.setProperty('--nt-a11y-text-scale', String(p.textScale / 100));
        // Mode d'affichage : seul le mode sombre est réappliqué (le mode clair
        // est l'affichage par défaut). Les autres réglages retirés du panneau
        // (thèmes, zoom interface, animations, sans distractions, lecture
        // simplifiée, raccourcis) ne sont plus jamais appliqués.
        if (p.theme === 'dark' && !p.highContrast) h.setAttribute('data-nt-a11y-theme', 'dark');
        if (p.highContrast) h.setAttribute('data-nt-a11y-contrast', 'high');
        if (p.font && p.font !== 'default') h.setAttribute('data-nt-a11y-font', p.font);
        if (p.underlineLinks) h.setAttribute('data-nt-a11y-links', 'on');
        if (p.colorPatterns !== false) h.setAttribute('data-nt-a11y-patterns', 'on');
        if (p.bigCursor) h.setAttribute('data-nt-a11y-cursor', 'on');
        h.setAttribute('data-nt-a11y-focusring', 'on');
        if (p.brightness && p.brightness !== 100) {
            h.setAttribute('data-nt-a11y-brightness', String(p.brightness));
            s.setProperty('--nt-a11y-brightness-pct', String(p.brightness));
        }
        var letter = Number(p.letterSpacing) || 0;
        var word = Number(p.wordSpacing) || 0;
        var line = Number(p.lineHeight) || 0;
        var para = Number(p.paragraphSpacing) || 0;
        if (letter || word || line) {
            s.setProperty('--nt-a11y-letter', (letter * 0.04) + 'em');
            s.setProperty('--nt-a11y-word', (word * 0.08) + 'em');
            s.setProperty('--nt-a11y-line', String(line * 0.15));
            if (letter) h.setAttribute('data-nt-a11y-letter', '1');
            if (word) h.setAttribute('data-nt-a11y-word', '1');
            if (line) h.setAttribute('data-nt-a11y-line', '1');
        }
        if (para) {
            s.setProperty('--nt-a11y-para', (para * 0.35) + 'rem');
            h.setAttribute('data-nt-a11y-para', '1');
        }
    } catch (e) {}
})();
</script>
