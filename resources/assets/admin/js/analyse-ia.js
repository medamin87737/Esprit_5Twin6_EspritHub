/**
 * Module 2 — dropzone rapport + préremplissage IA + popup résumé.
 */
(function () {
    'use strict';

    const ALLOWED = ['txt', 'docx', 'text'];
    const MAX_BYTES = 5 * 1024 * 1024;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value
            || '';
    }

    function setField(name, value) {
        if (value == null || value === '') return;
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return;
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function status(box, ok, msg) {
        if (!box) return;
        box.hidden = false;
        box.className = 'nt-ia-status ' + (ok ? 'is-ok' : 'is-error');
        box.textContent = msg;
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function format(raw) {
        return escapeHtml(raw)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\b(non[-\s]?conforme)\b/gi, '<span style="color:#c0392b;font-weight:700">$1</span>')
            .replace(/\b(conforme)\b/gi, '<span style="color:#087443;font-weight:700">$1</span>');
    }

    function extensionOf(file) {
        const n = (file?.name || '').toLowerCase();
        const i = n.lastIndexOf('.');
        return i >= 0 ? n.slice(i + 1) : '';
    }

    function assignFile(input, file, nameEl, surface) {
        if (!file || !input) return false;

        const ext = extensionOf(file);
        if (!ALLOWED.includes(ext)) {
            status(document.querySelector('[data-ia-status]'), false, 'Format refusé : TXT ou DOCX uniquement.');
            return false;
        }
        if (file.size > MAX_BYTES) {
            status(document.querySelector('[data-ia-status]'), false, 'Fichier trop volumineux (max 5 Mo).');
            return false;
        }

        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));

        if (nameEl) {
            nameEl.textContent = file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' Ko)';
        }
        surface?.classList.add('has-file');
        surface?.classList.remove('is-dragover');
        return true;
    }

    function initDropzones() {
        document.querySelectorAll('[data-nt-dropzone]').forEach((wrap) => {
            if (wrap.dataset.ntReady) return;
            wrap.dataset.ntReady = '1';

            const input = wrap.querySelector('[data-nt-drop-input]');
            const surface = wrap.querySelector('[data-nt-drop-surface]');
            const nameEl = wrap.querySelector('[data-nt-drop-name]');
            if (!input || !surface) return;

            const stop = (e) => {
                e.preventDefault();
                e.stopPropagation();
            };

            ['dragenter', 'dragover'].forEach((evt) => {
                surface.addEventListener(evt, (e) => {
                    stop(e);
                    surface.classList.add('is-dragover');
                });
            });

            ['dragleave', 'dragend'].forEach((evt) => {
                surface.addEventListener(evt, (e) => {
                    stop(e);
                    surface.classList.remove('is-dragover');
                });
            });

            surface.addEventListener('drop', (e) => {
                stop(e);
                const file = e.dataTransfer?.files?.[0];
                assignFile(input, file, nameEl, surface);
            });

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (file) {
                    assignFile(input, file, nameEl, surface);
                } else if (nameEl) {
                    nameEl.textContent = 'Aucun fichier sélectionné';
                    surface.classList.remove('has-file');
                }
            });
        });

        // Empêche le navigateur d'ouvrir le fichier si drop hors zone
        window.addEventListener('dragover', (e) => e.preventDefault());
        window.addEventListener('drop', (e) => {
            if (!e.target.closest?.('[data-nt-dropzone]')) e.preventDefault();
        });
    }

    function modal() {
        let el = document.getElementById('nt-ia-modal');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'nt-ia-modal';
        el.innerHTML = `
<style>
#nt-ia-modal{position:fixed;inset:0;z-index:1080;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(6,32,19,.5)}
#nt-ia-modal.open{display:flex}
#nt-ia-modal .box{width:min(480px,100%);background:#fff;border-radius:14px;overflow:hidden;border:1px solid #d7e0d9;box-shadow:0 16px 40px rgba(0,0,0,.18)}
#nt-ia-modal .hd{background:#064e35;color:#fff;padding:1rem 1.1rem;position:relative}
#nt-ia-modal .hd small{display:block;color:#ffb627;font-weight:700;letter-spacing:.06em;text-transform:uppercase;font-size:.65rem;margin-bottom:.25rem}
#nt-ia-modal .hd h3{margin:0;font-size:1.1rem}
#nt-ia-modal .x{position:absolute;top:.6rem;right:.7rem;border:0;background:transparent;color:#fff;font-size:1.3rem;cursor:pointer}
#nt-ia-modal .bd{padding:1.1rem;min-height:5rem;line-height:1.65;color:#2a3b31;font-size:.95rem;white-space:pre-wrap}
#nt-ia-modal .ft{padding:0 1.1rem 1rem;text-align:right}
#nt-ia-modal .cur{display:inline-block;width:.5ch;background:#087443;animation:ntb .7s step-end infinite}
@keyframes ntb{50%{opacity:0}}
</style>
<div class="box" role="dialog" aria-modal="true">
  <div class="hd"><small>NutriTrace</small><h3 data-t>Résumé qualité</h3><button type="button" class="x" data-c aria-label="Fermer">&times;</button></div>
  <div class="bd" data-b>Lecture…</div>
  <div class="ft"><button type="button" class="btn btn-success btn-sm" data-c>Fermer</button></div>
</div>`;
        document.body.appendChild(el);
        el.addEventListener('click', (e) => {
            if (e.target === el || e.target.closest('[data-c]')) el.classList.remove('open');
        });
        return el;
    }

    function typewrite(node, text, speed) {
        return new Promise((resolve) => {
            let i = 0;
            const tick = () => {
                i++;
                node.innerHTML = format(text.slice(0, i)) + (i < text.length ? '<span class="cur">|</span>' : '');
                if (i < text.length) setTimeout(tick, speed);
                else resolve();
            };
            tick();
        });
    }

    document.addEventListener('DOMContentLoaded', initDropzones);
    initDropzones();

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-ia-prefill]');
        if (!btn) return;
        event.preventDefault();

        const form = btn.closest('form');
        const file = form?.querySelector('input[name="rapport"]');
        const box = form?.querySelector('[data-ia-status]');
        if (!file?.files?.length) {
            status(box, false, 'Glissez ou choisissez un rapport (TXT ou DOCX).');
            return;
        }

        const m = modal();
        const title = m.querySelector('[data-t]');
        const body = m.querySelector('[data-b]');
        title.textContent = 'Résumé qualité';
        body.textContent = 'Lecture du rapport…';
        m.classList.add('open');

        btn.disabled = true;
        const label = btn.innerHTML;
        btn.innerHTML = 'Analyse…';

        try {
            const fd = new FormData();
            fd.append('rapport', file.files[0]);
            const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            const token = csrf();
            if (token) headers['X-CSRF-TOKEN'] = token;

            const res = await fetch(btn.getAttribute('data-ia-prefill'), {
                method: 'POST', headers, body: fd, credentials: 'same-origin',
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.message || 'Échec IA');

            const d = json.data || {};
            ['type', 'resultat', 'date_prelevement', 'date_resultat', 'commentaire'].forEach((k) => setField(k, d[k]));

            title.textContent = d.titre_resume || 'Synthèse qualité';
            await typewrite(body, d.resume_qualite || d.resume_court || d.commentaire || 'Résumé indisponible.', 12);
            status(box, true, 'Champs préremplis — vérifiez avant d\'enregistrer.');
        } catch (err) {
            body.textContent = err.message || 'Erreur IA.';
            status(box, false, err.message || 'Erreur IA.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = label;
        }
    });
})();
