/**
 * Assistant chat qualité (consommateur) — fenêtre flottante + focus produit.
 */
const WELCOME =
    "Bonjour. Je suis l’assistant NutriTrace : qualité, conformité et orientation produit selon votre profil (ex. diabète / sucre). Ceci n’est pas un avis médical.";

const SUGGESTIONS = [
    'Quels produits sont conformes ?',
    'J’ai du diabète, quels produits me conseiller ?',
    'Y a-t-il des analyses non conformes ?',
];

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatText(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function bilanClass(bilan) {
    return (
        {
            conforme: 'is-ok',
            non_conforme: 'is-ko',
            en_attente: 'is-wait',
        }[bilan] || 'is-muted'
    );
}

export function initChatQualite() {
    const root = document.getElementById('nt-chat-qualite');
    const loginFallback = document.querySelector('[data-chat-produit]')?.dataset.loginUrl || '/connexion';

    /** @type {{id: number, nom: string}|null} */
    let produitFocus = null;

    /** @type {{role: string, content: string}[]} */
    const historique = [];
    let ouvert = false;
    let welcomeDone = false;
    let busy = false;

    const panel = root?.querySelector('#nt-chat-panel') || document.getElementById('nt-chat-panel');
    const messages = document.getElementById('nt-chat-messages');
    const form = document.getElementById('nt-chat-form');
    const input = document.getElementById('nt-chat-input');
    const openBtns = () => [...document.querySelectorAll('[data-nt-chat-open], #nt-chat-open')];
    const closeBtn = document.getElementById('nt-chat-close');
    const subtitle = root?.querySelector('.nt-chat__subtitle');
    const endpoint = root?.dataset.endpoint;
    const csrf = root?.dataset.csrf;

    if (root) {
        root.hidden = false;
    }

    function syncOpenBtns(expanded) {
        openBtns().forEach((btn) => {
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            btn.classList.toggle('is-open', expanded);
        });
    }

    function suggestionsPourProduit(nom) {
        return [
            `Ce produit « ${nom} » est-il conforme ?`,
            `J’ai du sucre / diabète : « ${nom} » est-il adapté ?`,
            `Quel risque qualité pour « ${nom} » ?`,
        ];
    }

    function scrollBas() {
        if (messages) messages.scrollTop = messages.scrollHeight;
    }

    function ajouterBulle(role, html) {
        if (!messages) return null;
        const el = document.createElement('div');
        el.className = `nt-chat__bubble nt-chat__bubble--${role}`;
        el.innerHTML = html;
        messages.appendChild(el);
        scrollBas();
        return el;
    }

    function cartesProduits(produits) {
        if (!Array.isArray(produits) || produits.length === 0) return '';

        const items = produits
            .map(
                (p) => `
            <a class="nt-chat__product ${bilanClass(p.bilan)}" href="${escapeHtml(p.url)}">
                <span class="nt-chat__product-name">${escapeHtml(p.nom)}</span>
                <span class="nt-chat__product-meta">${escapeHtml(p.bilan_label || '')}${p.categorie ? ' · ' + escapeHtml(p.categorie) : ''}</span>
                <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
            </a>`
            )
            .join('');

        return `<div class="nt-chat__products">${items}</div>`;
    }

    function suggestionsHtml(liste = SUGGESTIONS) {
        return `<div class="nt-chat__suggestions">${liste
            .map((s) => `<button type="button" class="nt-chat__chip" data-suggest="${escapeHtml(s)}">${escapeHtml(s)}</button>`)
            .join('')}</div>`;
    }

    function ajouterAssistant(texte, produits = [], avecSuggestions = false, suggestions = SUGGESTIONS) {
        const html =
            `<div class="nt-chat__text">${formatText(texte)}</div>` +
            cartesProduits(produits) +
            (avecSuggestions ? suggestionsHtml(suggestions) : '');
        ajouterBulle('assistant', html);
        historique.push({ role: 'assistant', content: texte });
    }

    function ajouterUser(texte) {
        ajouterBulle('user', `<div class="nt-chat__text">${formatText(texte)}</div>`);
        historique.push({ role: 'user', content: texte });
    }

    function setBusy(on) {
        busy = on;
        if (input) input.disabled = on;
        form?.querySelector('.nt-chat__send') && (form.querySelector('.nt-chat__send').disabled = on);
    }

    function typing(show) {
        if (!messages) return;
        const existing = messages.querySelector('.nt-chat__typing');
        if (existing) existing.remove();
        if (!show) return;
        const el = document.createElement('div');
        el.className = 'nt-chat__bubble nt-chat__bubble--assistant nt-chat__typing';
        el.innerHTML = '<span></span><span></span><span></span>';
        messages.appendChild(el);
        scrollBas();
    }

    function majSousTitre() {
        if (!subtitle) return;
        subtitle.textContent = produitFocus
            ? `Qualité · ${produitFocus.nom}`
            : 'NutriTrace · analyses & conformité';
    }

    function ouvrir(options = {}) {
        if (!root || !panel) {
            window.location.href = loginFallback;
            return;
        }

        const nouveauProduit = options.produit || null;
        const changeFocus =
            nouveauProduit &&
            (!produitFocus || Number(produitFocus.id) !== Number(nouveauProduit.id));

        if (changeFocus) {
            produitFocus = {
                id: Number(nouveauProduit.id),
                nom: String(nouveauProduit.nom || 'Produit'),
            };
            if (messages) messages.innerHTML = '';
            historique.length = 0;
            welcomeDone = false;
            majSousTitre();
        }

        ouvert = true;
        panel.hidden = false;
        syncOpenBtns(true);

        if (!welcomeDone) {
            welcomeDone = true;
            if (produitFocus) {
                ajouterAssistant(
                    `Bonjour 😊 Je suis l'assistant qualité pour « ${produitFocus.nom} ». Posez-moi vos questions sur la conformité, les analyses labo ou les lots de ce produit uniquement.`,
                    [],
                    true,
                    suggestionsPourProduit(produitFocus.nom)
                );
                // Première réponse automatique : bilan qualité du produit
                envoyer(`Parle-moi de la qualité de « ${produitFocus.nom} ».`, { silencieuxUser: false });
            } else {
                ajouterAssistant(WELCOME, [], true);
            }
        }

        input?.focus();
    }

    function fermer() {
        ouvert = false;
        if (panel) panel.hidden = true;
        syncOpenBtns(false);
    }

    function toggleChat() {
        if (ouvert) {
            fermer();
            return;
        }
        if (produitFocus) {
            produitFocus = null;
            if (messages) messages.innerHTML = '';
            historique.length = 0;
            welcomeDone = false;
            majSousTitre();
        }
        ouvrir();
    }

    async function envoyer(texte, opts = {}) {
        const message = String(texte || '').trim();
        if (!message || busy || !endpoint) return;

        if (!opts.silencieuxUser) {
            ajouterUser(message);
        }
        if (input) input.value = '';
        setBusy(true);
        typing(true);

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message,
                    produit_id: produitFocus?.id || null,
                    historique: historique.slice(0, -1).slice(-6),
                }),
            });

            const data = await res.json();
            typing(false);
            ajouterAssistant(
                data.reponse || "Je n'ai pas pu répondre pour le moment.",
                data.produits || []
            );
        } catch {
            typing(false);
            ajouterAssistant('Connexion interrompue. Réessayez dans un instant.');
        } finally {
            setBusy(false);
            input?.focus();
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-nt-chat-open], #nt-chat-open');
        if (!btn || btn.closest('[data-chat-produit]')) return;
        e.preventDefault();
        toggleChat();
    });
    closeBtn?.addEventListener('click', fermer);

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        envoyer(input?.value || '');
    });

    messages?.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-suggest]');
        if (chip) {
            envoyer(chip.getAttribute('data-suggest') || '');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ouvert) fermer();
    });

    // Avatars qualité sur les cartes / fiche produit
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-chat-produit]');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        if (!root) {
            window.location.href = btn.dataset.loginUrl || loginFallback;
            return;
        }

        ouvrir({
            produit: {
                id: btn.dataset.produitId,
                nom: btn.dataset.produitNom,
            },
        });
    });
}
