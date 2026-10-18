/**
 * Questionnaire profil + popup résultat risque (consommateur).
 */
function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatText(text) {
    return escapeHtml(text)
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}

function typewrite(node, text, speed = 16) {
    return new Promise((resolve) => {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const plain = String(text || '');
        if (reduce) {
            node.innerHTML = formatText(plain);
            resolve();
            return;
        }
        let i = 0;
        const tick = () => {
            i += 1;
            node.innerHTML =
                formatText(plain.slice(0, i)) +
                (i < plain.length ? '<span class="nt-risk-cur">|</span>' : '');
            if (i < plain.length) setTimeout(tick, speed);
            else resolve();
        };
        tick();
    });
}

function ensureModal() {
    let el = document.getElementById('nt-risk-modal');
    if (el) return el;

    el = document.createElement('div');
    el.id = 'nt-risk-modal';
    el.innerHTML = `
<style>
#nt-risk-modal{position:fixed;inset:0;z-index:1090;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(32,43,40,.5)}
#nt-risk-modal.open{display:flex}
#nt-risk-modal .box{width:min(560px,100%);background:#fff;border-radius:16px;border:1px solid #dde6e1;box-shadow:0 20px 50px rgba(32,43,40,.22);overflow:hidden}
#nt-risk-modal .hd{background:#087f5b;color:#fff;padding:1.1rem 1.25rem;position:relative}
#nt-risk-modal .hd small{display:block;color:#c8f0de;font-size:.65rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;margin-bottom:.3rem}
#nt-risk-modal .hd h3{margin:0;font-size:1.15rem;font-weight:700}
#nt-risk-modal .x{position:absolute;top:.7rem;right:.8rem;border:0;background:transparent;color:#fff;font-size:1.4rem;cursor:pointer;line-height:1}
#nt-risk-modal .bd{padding:1.25rem;color:#202b28}
#nt-risk-modal .ft{padding:0 1.25rem 1.2rem;display:flex;justify-content:flex-end;gap:.5rem}
#nt-risk-modal .btn{border:0;border-radius:.7rem;padding:.6rem 1rem;font-weight:650;cursor:pointer}
#nt-risk-modal .btn-primary{background:#087f5b;color:#fff}
#nt-risk-modal .btn-ghost{background:#fff;color:#202b28;border:1px solid #dde6e1}
#nt-risk-modal .q-list{display:grid;gap:.55rem;margin:0 0 1rem}
#nt-risk-modal .q-item{display:flex;gap:.65rem;align-items:flex-start;padding:.7rem .8rem;border:1px solid #dde6e1;border-radius:.75rem;background:#f8fbf9}
#nt-risk-modal .q-item input{margin-top:.2rem}
#nt-risk-modal label.nt-q-label{display:block;font-size:.82rem;font-weight:650;margin-bottom:.35rem}
#nt-risk-modal textarea,#nt-risk-modal .nt-q-input{width:100%;border:1px solid #dde6e1;border-radius:.7rem;padding:.7rem .85rem;font:inherit}
#nt-risk-modal .hint{font-size:.78rem;color:#697772;margin:.45rem 0 0}
#nt-risk-modal .score-row{display:flex;gap:1rem;align-items:center;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #dde6e1}
#nt-risk-modal .score-n{font-size:2.4rem;font-weight:800;line-height:1;color:#087f5b}
#nt-risk-modal .score-n.is-moyen{color:#b86e13}
#nt-risk-modal .score-n.is-eleve{color:#c83d45}
#nt-risk-modal .score-meta{font-size:.9rem;color:#697772}
#nt-risk-modal .verdict{font-size:1.02rem;line-height:1.65;min-height:3.5rem}
#nt-risk-modal .nt-risk-cur{display:inline-block;width:.45ch;height:1em;background:#087f5b;margin-left:1px;vertical-align:-2px;animation:ntrc .7s step-end infinite}
@keyframes ntrc{50%{opacity:0}}
@media (prefers-reduced-motion:reduce){#nt-risk-modal .nt-risk-cur{animation:none}}
</style>
<div class="box" role="dialog" aria-modal="true" aria-labelledby="nt-risk-title">
  <div class="hd">
    <small>NutriTrace</small>
    <h3 id="nt-risk-title" data-title>Évaluation</h3>
    <button type="button" class="x" data-close aria-label="Fermer">&times;</button>
  </div>
  <div class="bd" data-body></div>
  <div class="ft" data-footer></div>
</div>`;
    document.body.appendChild(el);

    const close = () => el.classList.remove('open');
    el.addEventListener('click', (e) => {
        if (e.target === el || e.target.closest('[data-close]')) close();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && el.classList.contains('open')) close();
    });

    return el;
}

function openModal({ title, bodyHtml, footerHtml }) {
    const el = ensureModal();
    el.querySelector('[data-title]').textContent = title;
    el.querySelector('[data-body]').innerHTML = bodyHtml;
    el.querySelector('[data-footer]').innerHTML = footerHtml;
    el.classList.add('open');
    return el;
}

function buildProfil(form) {
    const checks = [...form.querySelectorAll('input[name="profil_opt"]:checked')].map((i) => i.value);
    const detail = (form.querySelector('[name="profil_detail"]')?.value || '').trim();
    const parts = [...checks];
    if (detail) parts.push(detail);
    return parts.join(', ').slice(0, 240);
}

function verdictText(data) {
    const aptitude = data.aptitude || '';
    const produit = data.produit || 'ce produit';
    let head = '';
    if (aptitude === 'adapte') {
        head = `Verdict : « ${produit} » peut convenir à votre profil, sous réserve de l'étiquette nutritionnelle et d'un avis médical.`;
    } else if (aptitude === 'prudence') {
        head = `Verdict : « ${produit} » demande de la prudence pour votre profil. Une consommation modérée et un avis professionnel sont recommandés.`;
    } else if (aptitude === 'deconseille') {
        head = `Verdict : « ${produit} » n'est pas recommandé pour votre profil dans l'état actuel des informations.`;
    } else {
        head = `Verdict qualité : ${data.libelle || 'évaluation'} (${data.score ?? '—'}/100) pour « ${produit} ».`;
    }
    const conseil = (data.conseil_sante || '').trim();
    return conseil ? `${head}\n\n${conseil}` : `${head}\n\nCeci n'est pas un avis médical.`;
}

export function openProfilQuestionnaire({ action, csrf, produit }) {
    const el = openModal({
        title: 'Questionnaire de profil',
        bodyHtml: `
            <p class="hint" style="margin-top:0;margin-bottom:1rem">
                Indiquez votre situation pour une orientation personnalisée sur « ${escapeHtml(produit)} ».
                Aucun diagnostic médical n'est délivré.
            </p>
            <form id="nt-risk-q-form" action="${escapeHtml(action)}" method="POST">
                <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                <input type="hidden" name="profil_sante" id="nt-risk-profil-hidden" value="">
                <div class="q-list">
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="diabète / sucre"> <span>Diabète / sensibilité au sucre</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="hypertension / sel"> <span>Hypertension / limitation du sel</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="allergie ou intolérance"> <span>Allergie ou intolérance alimentaire</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="régime particulier"> <span>Régime alimentaire particulier</span></label>
                </div>
                <label class="nt-q-label" for="nt-risk-detail">Précisions (optionnel)</label>
                <textarea id="nt-risk-detail" name="profil_detail" rows="3" maxlength="200" placeholder="Ex. diabète de type 2, intolérance au lactose…"></textarea>
                <p class="hint">Vous pouvez aussi laisser vide et lancer l'évaluation qualité seule.</p>
            </form>
        `,
        footerHtml: `
            <button type="button" class="btn btn-ghost" data-close>Annuler</button>
            <button type="button" class="btn btn-primary" id="nt-risk-q-submit">Lancer l'évaluation</button>
        `,
    });

    el.querySelector('#nt-risk-q-submit')?.addEventListener('click', () => {
        const form = el.querySelector('#nt-risk-q-form');
        if (!form) return;
        const hidden = form.querySelector('#nt-risk-profil-hidden');
        if (hidden) hidden.value = buildProfil(form);
        const btn = el.querySelector('#nt-risk-q-submit');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Analyse en cours…';
        }
        form.submit();
    });
}

export async function showRisqueResult(data) {
    const niveau = data.niveau || 'moyen';
    const scoreClass = niveau === 'eleve' ? 'is-eleve' : niveau === 'moyen' ? 'is-moyen' : '';
    const el = openModal({
        title: 'Résultat pour votre profil',
        bodyHtml: `
            <div class="score-row">
                <div>
                    <span class="score-n ${scoreClass}">${escapeHtml(String(data.score ?? '—'))}</span>
                    <span class="score-meta">/100</span>
                </div>
                <div class="score-meta">
                    <strong style="display:block;color:#202b28;font-size:1rem">${escapeHtml(data.libelle || '')}</strong>
                    ${data.aptitude_label ? `<span>${escapeHtml(data.aptitude_label)}</span>` : ''}
                </div>
            </div>
            <div class="verdict" data-verdict><span class="nt-risk-cur">|</span></div>
        `,
        footerHtml: `<button type="button" class="btn btn-primary" data-close>Fermer</button>`,
    });

    const node = el.querySelector('[data-verdict]');
    if (node) await typewrite(node, verdictText(data), 15);
}

window.ntShowRisqueResult = showRisqueResult;

export function initQualiteRisque() {
    document.querySelectorAll('[data-nt-open-risk-q]').forEach((btn) => {
        btn.addEventListener('click', () => {
            openProfilQuestionnaire({
                action: btn.dataset.action || '',
                csrf: btn.dataset.csrf || '',
                produit: btn.dataset.produit || 'ce produit',
            });
        });
    });

    const payload = document.getElementById('nt-risk-result-data');
    if (payload) {
        try {
            const data = JSON.parse(payload.textContent || '{}');
            if (data && typeof data === 'object' && data.score != null && data.auto_open) {
                showRisqueResult(data);
            }
        } catch (_) {
            // ignore
        }
    }
}

document.addEventListener('DOMContentLoaded', initQualiteRisque);
