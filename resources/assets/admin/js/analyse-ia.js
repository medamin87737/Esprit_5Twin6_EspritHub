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
#nt-ia-modal{position:fixed;inset:0;z-index:1080;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(20,35,28,.55);backdrop-filter:blur(3px)}
#nt-ia-modal.open{display:flex}
#nt-ia-modal .box{width:min(620px,100%);max-height:88vh;overflow-y:auto;background:#fff;border-radius:20px;border:1px solid #dde6e1;box-shadow:0 28px 70px rgba(6,60,38,.35);animation:ntpop .3s cubic-bezier(.2,.9,.3,1.2)}
@keyframes ntpop{from{opacity:0;transform:translateY(14px) scale(.97)}to{opacity:1;transform:none}}
#nt-ia-modal .hd{background:linear-gradient(120deg,#064e35,#0a8f52 70%,#12b76a);color:#fff;padding:1.15rem 1.3rem;position:relative;overflow:hidden}
#nt-ia-modal .hd::after{content:"";position:absolute;right:-30px;top:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.12)}
#nt-ia-modal .hd small{display:block;color:#ffd97a;font-weight:800;letter-spacing:.09em;text-transform:uppercase;font-size:.65rem;margin-bottom:.3rem}
#nt-ia-modal .hd h3{margin:0;font-size:1.18rem;font-weight:750;display:flex;align-items:center;gap:.55rem}
#nt-ia-modal .hd h3::before{content:"✦";display:grid;place-items:center;width:2rem;height:2rem;border-radius:50%;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.4);font-size:.95rem}
#nt-ia-modal .x{position:absolute;top:.65rem;right:.75rem;border:0;background:transparent;color:#fff;font-size:1.35rem;line-height:1;cursor:pointer}
#nt-ia-modal .bd{padding:1.2rem 1.25rem;min-height:5.5rem;line-height:1.7;color:#202b28;font-size:.95rem;white-space:pre-wrap}
#nt-ia-modal .nt-resume-sec{border-radius:.9rem;padding:.8rem .95rem;margin-bottom:.65rem;border:1px solid #e2ece4;border-left:5px solid #087f5b;background:#f4faf6;white-space:normal;animation:ntsec .35s ease}
#nt-ia-modal .nt-resume-sec h4{margin:0 0 .3rem;font-size:.86rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase}
#nt-ia-modal .nt-resume-sec p{margin:0;font-size:.93rem;line-height:1.6}
#nt-ia-modal .nt-resume-sec--verdict{border-left-color:#087f5b;background:linear-gradient(140deg,#eafaf0,#f7fdf9)}
#nt-ia-modal .nt-resume-sec--verdict h4{color:#0b5d33}
#nt-ia-modal .nt-resume-sec--confirmes{border-left-color:#2f9e6e;background:#f2faf5}
#nt-ia-modal .nt-resume-sec--confirmes h4{color:#12714a}
#nt-ia-modal .nt-resume-sec--vigilance{border-left-color:#e0a800;background:#fff9e6}
#nt-ia-modal .nt-resume-sec--vigilance h4{color:#8a5a00}
#nt-ia-modal .nt-resume-sec--recommandation{border-left-color:#3b6fe0;background:#eef4ff}
#nt-ia-modal .nt-resume-sec--recommandation h4{color:#274b9f}
@keyframes ntsec{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
#nt-ia-modal .ft{padding:0 1.25rem 1.15rem;display:flex;justify-content:flex-end}
#nt-ia-modal .ft button{border:0;border-radius:.7rem;background:#087f5b;color:#fff;padding:.55rem 1rem;font-weight:600;cursor:pointer}
#nt-ia-modal .cur{display:inline-block;width:.45ch;height:1em;background:#087f5b;margin-left:1px;vertical-align:-2px;animation:ntb .7s step-end infinite}
@media (prefers-reduced-motion:reduce){#nt-ia-modal .cur{animation:none} #nt-ia-modal .bd{transition:none}}
@keyframes ntb{50%{opacity:0}}
</style>
<div class="box" role="dialog" aria-modal="true" aria-labelledby="nt-ia-modal-title">
  <div class="hd"><small>NutriTrace · Synthèse IA</small><h3 id="nt-ia-modal-title" data-t>Synthèse qualité</h3><button type="button" class="x" data-c aria-label="Fermer">&times;</button></div>
  <div class="bd" data-b>Préparation de la synthèse…</div>
  <div class="ft"><button type="button" data-c>Fermer</button></div>
</div>`;
        document.body.appendChild(el);
        el.addEventListener('click', (e) => {
            if (e.target === el || e.target.closest('[data-c]')) el.classList.remove('open');
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && el.classList.contains('open')) el.classList.remove('open');
        });
        return el;
    }

    function typewrite(node, text, speed) {
        return new Promise((resolve) => {
            let i = 0;
            const plain = String(text || '');
            const tick = () => {
                i++;
                node.innerHTML = format(plain.slice(0, i)) + (i < plain.length ? '<span class="cur">|</span>' : '');
                if (i < plain.length) setTimeout(tick, speed);
                else resolve();
            };
            tick();
        });
    }

    const RESUME_SECTIONS = [
        { match: /^verdict/i, cls: 'verdict', icon: '⚖' },
        { match: /^points confirm/i, cls: 'confirmes', icon: '✓' },
        { match: /^points de vigilance/i, cls: 'vigilance', icon: '!' },
        { match: /^recommandation/i, cls: 'recommandation', icon: '➜' },
    ];

    function sectionsResume(text) {
        const lignes = String(text || '').split(/\n+/).map((l) => l.trim()).filter(Boolean);
        const sections = [];
        let courante = null;

        lignes.forEach((ligne) => {
            const trouvee = RESUME_SECTIONS.find((s) => s.match.test(ligne));
            if (trouvee) {
                courante = {
                    cls: trouvee.cls,
                    icon: trouvee.icon,
                    titre: ligne.replace(/:.*$/, ''),
                    texte: ligne.replace(/^[^:]+:\s*/, ''),
                };
                sections.push(courante);
            } else if (courante) {
                courante.texte += (courante.texte ? ' ' : '') + ligne;
            } else {
                courante = { cls: 'verdict', icon: '⚖', titre: 'Synthèse', texte: ligne };
                sections.push(courante);
            }
        });

        return sections;
    }

    function taperTexte(node, texte, vitesse) {
        return new Promise((resolve) => {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const plein = String(texte || '');
            if (reduce) {
                node.innerHTML = format(plein);
                resolve();
                return;
            }
            let i = 0;
            const tick = () => {
                i += 2;
                node.innerHTML = format(plein.slice(0, i)) + (i < plein.length ? '<span class="cur">|</span>' : '');
                if (i < plein.length) setTimeout(tick, vitesse);
                else resolve();
            };
            tick();
        });
    }

    async function showResume(text, titre) {
        const m = modal();
        const title = m.querySelector('[data-t]');
        const body = m.querySelector('[data-b]');
        title.textContent = titre || 'Synthèse qualité';
        m.classList.add('open');

        const sections = sectionsResume(text || 'Synthèse indisponible.');

        // Écriture lettre par lettre, section colorée après section colorée.
        body.innerHTML = sections.map((s) => `
            <div class="nt-resume-sec nt-resume-sec--${s.cls}">
                <h4>${s.icon} ${escapeHtml(s.titre)}</h4>
                <p data-sec-texte></p>
            </div>`).join('');

        const zones = [...body.querySelectorAll('[data-sec-texte]')];
        for (let i = 0; i < sections.length; i++) {
            await taperTexte(zones[i], sections[i].texte, 13);
        }
    }

    window.ntIaShowResume = showResume;
    document.dispatchEvent(new CustomEvent('nt-ia-ready'));

    function initLoadingForms() {
        document.querySelectorAll('[data-nt-loading-form]').forEach((form) => {
            if (form.dataset.ntLoadingReady) return;
            form.dataset.ntLoadingReady = '1';
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button[type="submit"]');
                if (!btn || btn.disabled) return;
                btn.disabled = true;
                btn.dataset.ntOriginal = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Traitement…';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDropzones();
        initLoadingForms();

        document.querySelectorAll('[data-nt-typewrite]').forEach((el) => {
            const full = el.getAttribute('data-nt-typewrite') || el.textContent || '';
            if (!full.trim()) return;
            el.textContent = '';
            typewrite(el, full, 12);
        });
    });
    initDropzones();
    initLoadingForms();

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
