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
#nt-risk-modal{position:fixed;inset:0;z-index:1090;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(20,35,28,.55);backdrop-filter:blur(3px)}
#nt-risk-modal.open{display:flex}
#nt-risk-modal .box{width:min(580px,100%);max-height:88vh;overflow-y:auto;background:#fff;border-radius:20px;border:1px solid #dde6e1;box-shadow:0 28px 70px rgba(6,60,38,.35);animation:ntriskpop .3s cubic-bezier(.2,.9,.3,1.2)}
@keyframes ntriskpop{from{opacity:0;transform:translateY(14px) scale(.97)}to{opacity:1;transform:none}}
#nt-risk-modal .hd{background:linear-gradient(120deg,#064e35,#0a8f52 70%,#12b76a);color:#fff;padding:1.15rem 1.3rem;position:relative;overflow:hidden}
#nt-risk-modal .hd::after{content:"";position:absolute;right:-30px;top:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.12)}
#nt-risk-modal .hd small{display:block;color:#ffd97a;font-size:.65rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;margin-bottom:.3rem}
#nt-risk-modal .hd h3{margin:0;font-size:1.18rem;font-weight:750;display:flex;align-items:center;gap:.55rem}
#nt-risk-modal .hd h3::before{content:"✦";display:grid;place-items:center;width:2rem;height:2rem;border-radius:50%;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.4);font-size:.95rem}
#nt-risk-modal .x{position:absolute;top:.7rem;right:.8rem;border:0;background:transparent;color:#fff;font-size:1.4rem;cursor:pointer;line-height:1;z-index:1}
#nt-risk-modal .bd{padding:1.3rem;color:#202b28}
#nt-risk-modal .ft{padding:0 1.3rem 1.25rem;display:flex;justify-content:flex-end;gap:.5rem}
#nt-risk-modal .btn{border:0;border-radius:.7rem;padding:.6rem 1.05rem;font-weight:650;cursor:pointer}
#nt-risk-modal .btn-primary{background:linear-gradient(120deg,#0a8f52,#087f5b);color:#fff;box-shadow:0 6px 16px rgba(8,127,91,.3)}
#nt-risk-modal .btn-ghost{background:#fff;color:#202b28;border:1px solid #dde6e1}
#nt-risk-modal .nt-hero{display:flex;gap:1.1rem;align-items:center;background:linear-gradient(135deg,#f2faf5,#e6f4ec);border:1px solid #d7ecdf;border-radius:16px;padding:1rem 1.1rem;margin-bottom:1rem}
#nt-risk-modal .nt-ring{position:relative;flex:0 0 auto;width:92px;height:92px}
#nt-risk-modal .nt-ring svg{transform:rotate(-90deg)}
#nt-risk-modal .nt-ring .nt-ring-val{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
#nt-risk-modal .nt-ring .nt-ring-val b{font-size:1.7rem;line-height:1;font-weight:800}
#nt-risk-modal .nt-ring .nt-ring-val span{font-size:.62rem;font-weight:700;color:#697772;letter-spacing:.05em}
#nt-risk-modal .nt-hero-meta strong{display:block;font-size:1.12rem;color:#17351f;margin-bottom:.3rem}
#nt-risk-modal .nt-verdict-card{border-left:5px solid #087f5b;background:#f8fbf9;border:1px solid #e2ece4;border-left:5px solid #087f5b;border-radius:12px;padding:.95rem 1.05rem;margin-top:1rem}
#nt-risk-modal .q-list{display:grid;gap:.55rem;margin:0 0 1rem}
#nt-risk-modal .q-item{display:flex;gap:.65rem;align-items:flex-start;padding:.7rem .8rem;border:1px solid #dde6e1;border-radius:.75rem;background:#f8fbf9}
#nt-risk-modal .q-item input{margin-top:.2rem}
#nt-risk-modal label.nt-q-label{display:block;font-size:.82rem;font-weight:650;margin-bottom:.35rem}
#nt-risk-modal textarea,#nt-risk-modal .nt-q-input{width:100%;border:1px solid #dde6e1;border-radius:.7rem;padding:.7rem .85rem;font:inherit}
#nt-risk-modal .hint{font-size:.78rem;color:#697772;margin:.45rem 0 0}
#nt-risk-modal .nt-gauge{height:.65rem;border-radius:999px;background:#e8efea;overflow:hidden;margin:.4rem 0 1rem}
#nt-risk-modal .nt-gauge span{display:block;height:100%;border-radius:999px;transition:width .6s ease}
#nt-risk-modal .nt-breakdown{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.35rem}
#nt-risk-modal .nt-chip{font-size:.74rem;font-weight:700;border-radius:999px;padding:.22rem .6rem}
#nt-risk-modal .nt-chip--qualite{background:#e5f6ec;color:#0b5d33}
#nt-risk-modal .nt-chip--profil{background:#fdeeda;color:#8a5a00}
#nt-risk-modal .nt-chip--total{background:#202b28;color:#fff}
#nt-risk-modal .nt-profils{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.8rem}
#nt-risk-modal .nt-profil-chip{font-size:.76rem;font-weight:700;border-radius:999px;padding:.25rem .65rem;background:linear-gradient(120deg,#6c4bd8,#9b7bff);color:#fff}
#nt-risk-modal .nt-aptitude{display:inline-block;margin-top:.3rem;font-size:.78rem;font-weight:700;border-radius:999px;padding:.22rem .6rem}
#nt-risk-modal .nt-aptitude.is-ok{background:#e5f6ec;color:#0b5d33}
#nt-risk-modal .nt-aptitude.is-wait{background:#fff3d6;color:#8a5a00}
#nt-risk-modal .nt-aptitude.is-ko{background:#fdecec;color:#8c1d18}
#nt-risk-modal .nt-detail-title{font-size:.92rem;font-weight:750;margin:.6rem 0 .5rem;color:#17351f}
#nt-risk-modal .nt-facteurs{display:grid;gap:.5rem;margin-bottom:1rem}
#nt-risk-modal .nt-facteur{display:flex;gap:.65rem;align-items:flex-start;background:#f8fbf9;border:1px solid #e2ece4;border-radius:.8rem;padding:.6rem .7rem}
#nt-risk-modal .nt-facteur-impact{flex:0 0 auto;color:#fff;font-size:.72rem;font-weight:800;border-radius:.55rem;padding:.28rem .45rem;margin-top:.1rem}
#nt-risk-modal .nt-facteur strong{font-size:.88rem;color:#202b28}
#nt-risk-modal .nt-facteur small{color:#697772}
#nt-risk-modal .nt-facteur p{margin:.15rem 0 0;font-size:.82rem;color:#4c5a50;line-height:1.5}
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
        head = `Verdict : « ${produit} » peut convenir à votre profil. Les facteurs détectés dans sa composition restent favorables.`;
    } else if (aptitude === 'prudence') {
        head = `Verdict : « ${produit} » demande de la prudence pour votre profil : certains facteurs de sa composition font monter le score. Consommez-le en portion maîtrisée, pas quotidiennement.`;
    } else if (aptitude === 'deconseille') {
        head = `Verdict : « ${produit} » est peu recommandé pour votre profil dans l'état actuel des informations. Mieux vaut choisir une alternative plus adaptée.`;
    } else {
        head = `Verdict qualité : ${data.libelle || 'évaluation'} (${data.score ?? '—'}/100) pour « ${produit} ».`;
    }

    let texte = head;
    if (data.justification) texte += `\n\n${data.justification}`;

    const conseils = Array.isArray(data.conseils) && data.conseils.length
        ? data.conseils
        : (data.conseil_sante ? [data.conseil_sante] : []);
    if (conseils.length) {
        texte += '\n\n**Conseils personnalisés :**\n' + conseils.map((c) => `• ${c}`).join('\n');
    }

    return texte;
}

function couleurImpact(impact) {
    if (impact >= 14) return '#c83d45';
    if (impact > 0) return '#b86e13';
    return '#087f5b';
}

function detailRisqueHtml(data) {
    let html = '';

    const profils = Array.isArray(data.profils_detectes) ? data.profils_detectes : [];
    if (profils.length) {
        html += '<div class="nt-profils">' + profils.map((p) =>
            `<span class="nt-profil-chip">${escapeHtml(p.label || '')}</span>`).join('') + '</div>';
    }

    const facteurs = Array.isArray(data.facteurs) ? data.facteurs : [];
    if (facteurs.length) {
        html += '<h4 class="nt-detail-title">Pourquoi ce score ? Facteurs détectés</h4><div class="nt-facteurs">'
            + facteurs.map((f) => `
                <div class="nt-facteur">
                    <span class="nt-facteur-impact" style="background:${couleurImpact(Number(f.impact || 0))}">${Number(f.impact) > 0 ? '+' : ''}${escapeHtml(String(f.impact))} pts</span>
                    <div>
                        <strong>${escapeHtml(f.libelle || '')}</strong> <small>· ${escapeHtml(f.profil || '')}</small>
                        <p>${escapeHtml(f.detail || '')}</p>
                    </div>
                </div>`).join('')
            + '</div>';
    }

    return html;
}

export function openProfilQuestionnaire({ action, csrf, produit }) {
    const el = openModal({
        title: 'Questionnaire de profil',
        bodyHtml: `
            <p class="hint" style="margin-top:0;margin-bottom:1rem">
                Indiquez votre situation pour une orientation personnalisée sur « ${escapeHtml(produit)} ».
                Plus le profil est précis, plus le score et les conseils seront ciblés et détaillés.
            </p>
            <form id="nt-risk-q-form" action="${escapeHtml(action)}" method="POST">
                <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                <input type="hidden" name="profil_sante" id="nt-risk-profil-hidden" value="">
                <div class="q-list">
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="diabète / sucre"> <span>Diabète / sensibilité au sucre</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="hypertension / sel"> <span>Hypertension / limitation du sel</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="cholestérol / graisses"> <span>Cholestérol / graisses à surveiller</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="allergie ou intolérance"> <span>Allergie ou intolérance alimentaire</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="grossesse"> <span>Grossesse</span></label>
                    <label class="q-item"><input type="checkbox" name="profil_opt" value="enfant"> <span>Pour un enfant</span></label>
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
    const couleurScore = niveau === 'eleve' ? '#c83d45' : niveau === 'moyen' ? '#b86e13' : '#0a8f52';
    const aptitudeClass = data.aptitude === 'deconseille' ? 'is-ko' : data.aptitude === 'prudence' ? 'is-wait' : 'is-ok';
    const score = Math.max(0, Math.min(100, Number(data.score ?? 0)));
    const circonference = 2 * Math.PI * 40;
    const el = openModal({
        title: 'Résultat pour votre profil',
        bodyHtml: `
            <div class="nt-hero">
                <div class="nt-ring">
                    <svg width="92" height="92" viewBox="0 0 92 92">
                        <circle cx="46" cy="46" r="40" fill="none" stroke="#dcefe2" stroke-width="9"/>
                        <circle cx="46" cy="46" r="40" fill="none" stroke="${couleurScore}" stroke-width="9"
                            stroke-linecap="round" stroke-dasharray="${circonference}"
                            stroke-dashoffset="${circonference * (1 - score / 100)}"/>
                    </svg>
                    <div class="nt-ring-val"><b style="color:${couleurScore}">${escapeHtml(String(data.score ?? '—'))}</b><span>/ 100</span></div>
                </div>
                <div class="nt-hero-meta">
                    <strong>${escapeHtml(data.libelle || '')}</strong>
                    ${data.aptitude_label ? `<span class="nt-aptitude ${aptitudeClass}">${escapeHtml(data.aptitude_label)}</span>` : ''}
                </div>
            </div>
            ${detailRisqueHtml(data)}
            <div class="nt-verdict-card verdict" data-verdict><span class="nt-risk-cur">|</span></div>
        `,
        footerHtml: `<button type="button" class="btn btn-primary" data-close>Fermer</button>`,
    });

    const node = el.querySelector('[data-verdict]');
    if (node) await typewrite(node, verdictText(data), 11);
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
