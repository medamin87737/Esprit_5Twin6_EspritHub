@can('espace-consommateur')
<div
    id="nt-chat-qualite"
    class="nt-chat nt-chat--embedded"
    data-endpoint="{{ route('consommateur.chat-qualite') }}"
    data-csrf="{{ csrf_token() }}"
    hidden
>
    <section id="nt-chat-panel" class="nt-chat__panel" role="dialog" aria-labelledby="nt-chat-title" aria-modal="true" hidden>
        <header class="nt-chat__header">
            <div class="nt-chat__brand">
                <span class="nt-chat__avatar" aria-hidden="true"><i class="bi bi-clipboard2-pulse"></i></span>
                <div>
                    <h2 id="nt-chat-title" class="nt-chat__title">Assistant Qualité</h2>
                    <p class="nt-chat__subtitle">Analyses · conformité · profil</p>
                </div>
            </div>
            <button type="button" class="nt-chat__close" id="nt-chat-close" aria-label="Fermer">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="nt-chat__messages" id="nt-chat-messages" aria-live="polite"></div>

        <form class="nt-chat__form" id="nt-chat-form" autocomplete="off">
            <label class="visually-hidden" for="nt-chat-input">Votre question qualité</label>
            <input
                type="text"
                id="nt-chat-input"
                class="nt-chat__input"
                maxlength="800"
                placeholder="Ex. J’ai du diabète, ce produit est-il adapté ?"
                required
            >
            <button type="submit" class="nt-chat__send" id="nt-chat-send" aria-label="Envoyer">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </section>
</div>
@endcan
