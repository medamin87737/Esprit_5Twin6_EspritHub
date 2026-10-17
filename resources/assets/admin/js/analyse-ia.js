/**
 * Module 2 — pré-remplissage IA d'une analyse à partir du PDF (OpenRouter).
 */
(function () {
    'use strict';

    function csrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta?.content) {
            return meta.content;
        }
        const input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    function cookie(name) {
        const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function setField(name, value) {
        if (value === null || value === undefined || value === '') {
            return;
        }
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) {
            return;
        }
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function showStatus(box, ok, message) {
        if (!box) {
            return;
        }
        box.hidden = false;
        box.className = 'nt-ia-status ' + (ok ? 'is-ok' : 'is-error');
        box.textContent = message;
    }

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-ia-prefill]');
        if (!btn) {
            return;
        }

        event.preventDefault();
        const url = btn.getAttribute('data-ia-prefill');
        const form = btn.closest('form');
        const file = form?.querySelector('input[name="rapport"]');
        const status = form?.querySelector('[data-ia-status]');

        if (!file?.files?.length) {
            showStatus(status, false, 'Choisissez d\'abord un rapport PDF.');
            return;
        }

        const body = new FormData();
        body.append('rapport', file.files[0]);

        btn.disabled = true;
        const label = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split mr-1" aria-hidden="true"></i> Analyse IA…';
        showStatus(status, true, 'Lecture du PDF via OpenRouter…');

        try {
            const headers = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            const token = csrf();
            if (token) {
                headers['X-CSRF-TOKEN'] = token;
            }
            const xsrf = cookie('XSRF-TOKEN');
            if (xsrf) {
                headers['X-XSRF-TOKEN'] = xsrf;
            }

            const res = await fetch(url, { method: 'POST', headers, body, credentials: 'same-origin' });
            const json = await res.json();

            if (!json.ok) {
                throw new Error(json.message || 'Échec de l\'analyse IA.');
            }

            const d = json.data || {};
            setField('type', d.type);
            setField('resultat', d.resultat);
            setField('date_prelevement', d.date_prelevement);
            setField('date_resultat', d.date_resultat);
            setField('commentaire', d.commentaire);

            showStatus(status, true, json.message + (d.resume_court ? ' — ' + d.resume_court : ''));
        } catch (err) {
            showStatus(status, false, err.message || 'Erreur IA.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = label;
        }
    });
})();
