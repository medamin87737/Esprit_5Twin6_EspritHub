<a class="nt-a11y-skip" href="#contenu-principal">Aller au contenu principal</a>
<a class="nt-a11y-skip" href="#navigation-principale" style="left: 12.5rem;">Aller à la navigation</a>

<button
    type="button"
    id="nt-a11y-fab"
    class="nt-a11y-fab"
    aria-expanded="false"
    aria-controls="nt-a11y-panel"
    title="Accessibilité et affichage"
>
    <i class="bi bi-sliders" aria-hidden="true"></i>
    <span class="visually-hidden sr-only">Ouvrir les options d’accessibilité</span>
</button>

<button type="button" id="nt-a11y-backdrop" class="nt-a11y-backdrop" hidden aria-hidden="true" tabindex="-1"></button>

<aside
    id="nt-a11y-panel"
    class="nt-a11y-panel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="nt-a11y-title"
    hidden
>
    <header class="nt-a11y-panel__header">
        <div class="nt-a11y-panel__brand">
            <span class="nt-a11y-panel__brand-icon" aria-hidden="true"><i class="bi bi-sliders"></i></span>
            <div>
                <h2 id="nt-a11y-title" class="nt-a11y-panel__title">Accessibilité</h2>
                <p class="nt-a11y-panel__sub">Affichage · lecture · navigation</p>
            </div>
        </div>
        <button type="button" id="nt-a11y-close" class="nt-a11y-panel__close" aria-label="Fermer le panneau">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </header>

    <div class="nt-a11y-panel__body">
        <p id="nt-a11y-summary" class="nt-a11y-summary" role="status">Aucun réglage personnalisé actif.</p>

        {{-- A. Confort visuel --}}
        <div class="nt-a11y-cat nt-a11y-cat--eye">
            <button type="button" class="nt-a11y-cat__btn" aria-expanded="true" aria-controls="nt-a11y-cat-confort">
                <span class="nt-a11y-cat__icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
                <span class="nt-a11y-cat__label">Confort visuel</span>
                <i class="bi bi-chevron-down nt-a11y-cat__chevron" aria-hidden="true"></i>
            </button>
            <div id="nt-a11y-cat-confort" class="nt-a11y-cat__body">
                <div class="nt-a11y-dials">
                    <div class="nt-a11y-dial nt-a11y-dial--amber" data-a11y-dial>
                        <div class="nt-a11y-dial__ring">
                            <svg viewBox="0 0 80 80" aria-hidden="true">
                                <defs>
                                    <linearGradient id="ntDialAmber" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#f6ad55"/>
                                        <stop offset="100%" stop-color="#dd6b20"/>
                                    </linearGradient>
                                </defs>
                                <circle class="nt-a11y-dial__track" cx="40" cy="40" r="30"/>
                                <circle class="nt-a11y-dial__progress" cx="40" cy="40" r="30" stroke="url(#ntDialAmber)"/>
                            </svg>
                            <span class="nt-a11y-dial__knob" aria-hidden="true"></span>
                            <span class="nt-a11y-dial__center" data-a11y-label="blueFilter">Off</span>
                        </div>
                        <span class="nt-a11y-dial__name"><i class="bi bi-sun" aria-hidden="true"></i> Filtre chaud</span>
                        <input type="range" min="0" max="4" step="1" value="0" data-a11y="blueFilter" class="nt-a11y-dial__input" aria-label="Intensité du filtre chaud">
                    </div>

                    <div class="nt-a11y-dial nt-a11y-dial--lime" data-a11y-dial>
                        <div class="nt-a11y-dial__ring">
                            <svg viewBox="0 0 80 80" aria-hidden="true">
                                <defs>
                                    <linearGradient id="ntDialLime" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#4ade80"/>
                                        <stop offset="100%" stop-color="#15803d"/>
                                    </linearGradient>
                                </defs>
                                <circle class="nt-a11y-dial__track" cx="40" cy="40" r="30"/>
                                <circle class="nt-a11y-dial__progress" cx="40" cy="40" r="30" stroke="url(#ntDialLime)"/>
                            </svg>
                            <span class="nt-a11y-dial__knob" aria-hidden="true"></span>
                            <span class="nt-a11y-dial__center" data-a11y-label="brightness">100 %</span>
                        </div>
                        <span class="nt-a11y-dial__name"><i class="bi bi-brightness-high" aria-hidden="true"></i> Luminosité</span>
                        <input type="range" min="70" max="100" step="5" value="100" data-a11y="brightness" class="nt-a11y-dial__input" aria-label="Luminosité de l’interface">
                    </div>
                </div>

                <div class="nt-a11y-ctrl">
                    <div class="nt-a11y-ctrl__label">
                        <span><i class="bi bi-moon-stars nt-a11y-ctrl__glyph" aria-hidden="true"></i> Mode d’affichage</span>
                    </div>
                    <div class="nt-a11y-mode" role="group" aria-label="Mode clair ou sombre">
                        <button type="button" class="nt-a11y-mode__btn nt-a11y-mode__btn--light" data-a11y-theme-btn="light" aria-pressed="true">
                            <span class="nt-a11y-mode__icon" aria-hidden="true"><i class="bi bi-sun-fill"></i></span>
                            <span class="nt-a11y-mode__text">
                                <strong>Clair</strong>
                                <small>Mode blanc</small>
                            </span>
                        </button>
                        <button type="button" class="nt-a11y-mode__btn nt-a11y-mode__btn--dark" data-a11y-theme-btn="dark" aria-pressed="false">
                            <span class="nt-a11y-mode__icon" aria-hidden="true"><i class="bi bi-moon-stars-fill"></i></span>
                            <span class="nt-a11y-mode__text">
                                <strong>Sombre</strong>
                                <small>Mode nuit</small>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- B. Texte --}}
        <div class="nt-a11y-cat nt-a11y-cat--text">
            <button type="button" class="nt-a11y-cat__btn" aria-expanded="false" aria-controls="nt-a11y-cat-texte">
                <span class="nt-a11y-cat__icon" aria-hidden="true"><i class="bi bi-type"></i></span>
                <span class="nt-a11y-cat__label">Texte</span>
                <i class="bi bi-chevron-down nt-a11y-cat__chevron" aria-hidden="true"></i>
            </button>
            <div id="nt-a11y-cat-texte" class="nt-a11y-cat__body" hidden>
                <div class="nt-a11y-ctrl">
                    <div class="nt-a11y-ctrl__label">
                        <span><i class="bi bi-fonts nt-a11y-ctrl__glyph" aria-hidden="true"></i> Taille du texte</span>
                        <span class="nt-a11y-value nt-a11y-value--blue" data-a11y-label="textScale">100 %</span>
                    </div>
                    <div class="nt-a11y-pot nt-a11y-pot--blue" data-a11y-pot>
                        <div class="nt-a11y-pot__track">
                            <span class="nt-a11y-pot__fill" aria-hidden="true"></span>
                            <span class="nt-a11y-pot__thumb" aria-hidden="true"></span>
                            <input type="range" min="80" max="200" step="10" value="100" data-a11y="textScale" class="nt-a11y-pot__input" aria-label="Taille du texte">
                        </div>
                        <div class="nt-a11y-pot__ends" aria-hidden="true"><span>A</span><span style="font-size:1.15rem">A</span></div>
                    </div>
                </div>

                <div class="nt-a11y-ctrl">
                    <label class="nt-a11y-ctrl__label" for="nt-a11y-font">Police</label>
                    <select id="nt-a11y-font" data-a11y="font" class="nt-a11y-select">
                        <option value="default">Police du site</option>
                        <option value="system">Système</option>
                        <option value="readable">Lisible (Verdana)</option>
                    </select>
                </div>

                <div class="nt-a11y-ctrl nt-a11y-ctrl--grid">
                    <div class="nt-a11y-pot nt-a11y-pot--cyan nt-a11y-pot--compact" data-a11y-pot>
                        <label class="nt-a11y-ctrl__label" for="nt-a11y-letter">
                            <span>Lettres</span>
                            <span class="nt-a11y-value nt-a11y-value--cyan" data-a11y-label="letterSpacing">0</span>
                        </label>
                        <div class="nt-a11y-pot__track">
                            <span class="nt-a11y-pot__fill" aria-hidden="true"></span>
                            <span class="nt-a11y-pot__thumb" aria-hidden="true"></span>
                            <input id="nt-a11y-letter" type="range" min="0" max="3" step="1" value="0" data-a11y="letterSpacing" class="nt-a11y-pot__input" aria-label="Espacement des lettres">
                        </div>
                    </div>
                    <div class="nt-a11y-pot nt-a11y-pot--cyan nt-a11y-pot--compact" data-a11y-pot>
                        <label class="nt-a11y-ctrl__label" for="nt-a11y-word">
                            <span>Mots</span>
                            <span class="nt-a11y-value nt-a11y-value--cyan" data-a11y-label="wordSpacing">0</span>
                        </label>
                        <div class="nt-a11y-pot__track">
                            <span class="nt-a11y-pot__fill" aria-hidden="true"></span>
                            <span class="nt-a11y-pot__thumb" aria-hidden="true"></span>
                            <input id="nt-a11y-word" type="range" min="0" max="3" step="1" value="0" data-a11y="wordSpacing" class="nt-a11y-pot__input" aria-label="Espacement des mots">
                        </div>
                    </div>
                    <div class="nt-a11y-pot nt-a11y-pot--teal nt-a11y-pot--compact" data-a11y-pot>
                        <label class="nt-a11y-ctrl__label" for="nt-a11y-line">
                            <span>Interligne</span>
                            <span class="nt-a11y-value nt-a11y-value--teal" data-a11y-label="lineHeight">0</span>
                        </label>
                        <div class="nt-a11y-pot__track">
                            <span class="nt-a11y-pot__fill" aria-hidden="true"></span>
                            <span class="nt-a11y-pot__thumb" aria-hidden="true"></span>
                            <input id="nt-a11y-line" type="range" min="0" max="3" step="1" value="0" data-a11y="lineHeight" class="nt-a11y-pot__input" aria-label="Hauteur de ligne">
                        </div>
                    </div>
                    <div class="nt-a11y-pot nt-a11y-pot--teal nt-a11y-pot--compact" data-a11y-pot>
                        <label class="nt-a11y-ctrl__label" for="nt-a11y-para">
                            <span>Paragraphes</span>
                            <span class="nt-a11y-value nt-a11y-value--teal" data-a11y-label="paragraphSpacing">0</span>
                        </label>
                        <div class="nt-a11y-pot__track">
                            <span class="nt-a11y-pot__fill" aria-hidden="true"></span>
                            <span class="nt-a11y-pot__thumb" aria-hidden="true"></span>
                            <input id="nt-a11y-para" type="range" min="0" max="3" step="1" value="0" data-a11y="paragraphSpacing" class="nt-a11y-pot__input" aria-label="Espacement des paragraphes">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- C. Couleurs et contraste --}}
        <div class="nt-a11y-cat nt-a11y-cat--contrast">
            <button type="button" class="nt-a11y-cat__btn" aria-expanded="false" aria-controls="nt-a11y-cat-couleurs">
                <span class="nt-a11y-cat__icon" aria-hidden="true"><i class="bi bi-palette"></i></span>
                <span class="nt-a11y-cat__label">Contraste &amp; couleurs</span>
                <i class="bi bi-chevron-down nt-a11y-cat__chevron" aria-hidden="true"></i>
            </button>
            <div id="nt-a11y-cat-couleurs" class="nt-a11y-cat__body" hidden>
                <div class="nt-a11y-toggles">
                    <label class="nt-a11y-switch nt-a11y-switch--violet">
                        <input type="checkbox" data-a11y="highContrast">
                        <span class="nt-a11y-switch__ui" aria-hidden="true">
                            <span class="nt-a11y-switch__track"><span class="nt-a11y-switch__knob"></span></span>
                        </span>
                        <span class="nt-a11y-switch__label">
                            <span class="nt-a11y-switch__icon"><i class="bi bi-circle-half"></i></span>
                            Contraste élevé
                        </span>
                    </label>
                    <label class="nt-a11y-switch nt-a11y-switch--indigo">
                        <input type="checkbox" data-a11y="underlineLinks">
                        <span class="nt-a11y-switch__ui" aria-hidden="true">
                            <span class="nt-a11y-switch__track"><span class="nt-a11y-switch__knob"></span></span>
                        </span>
                        <span class="nt-a11y-switch__label">
                            <span class="nt-a11y-switch__icon"><i class="bi bi-link-45deg"></i></span>
                            Liens soulignés
                        </span>
                    </label>
                    <label class="nt-a11y-switch nt-a11y-switch--fuchsia">
                        <input type="checkbox" data-a11y="colorPatterns" checked>
                        <span class="nt-a11y-switch__ui" aria-hidden="true">
                            <span class="nt-a11y-switch__track"><span class="nt-a11y-switch__knob"></span></span>
                        </span>
                        <span class="nt-a11y-switch__label">
                            <span class="nt-a11y-switch__icon"><i class="bi bi-check2-square"></i></span>
                            Symboles sur statuts
                        </span>
                    </label>
                    <label class="nt-a11y-switch nt-a11y-switch--sky">
                        <input type="checkbox" data-a11y="bigCursor">
                        <span class="nt-a11y-switch__ui" aria-hidden="true">
                            <span class="nt-a11y-switch__track"><span class="nt-a11y-switch__knob"></span></span>
                        </span>
                        <span class="nt-a11y-switch__label">
                            <span class="nt-a11y-switch__icon"><i class="bi bi-cursor"></i></span>
                            Curseur visible
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- D. Lecture vocale --}}
        <div class="nt-a11y-cat nt-a11y-cat--nav">
            <button type="button" class="nt-a11y-cat__btn" aria-expanded="false" aria-controls="nt-a11y-cat-nav">
                <span class="nt-a11y-cat__icon" aria-hidden="true"><i class="bi bi-compass"></i></span>
                <span class="nt-a11y-cat__label">Lecture vocale</span>
                <i class="bi bi-chevron-down nt-a11y-cat__chevron" aria-hidden="true"></i>
            </button>
            <div id="nt-a11y-cat-nav" class="nt-a11y-cat__body" hidden>
                <div class="nt-a11y-ctrl">
                    <span class="nt-a11y-ctrl__label"><i class="bi bi-volume-up nt-a11y-ctrl__glyph" aria-hidden="true"></i> Lecture vocale</span>
                    <div class="nt-a11y-speech" data-state="idle">
                        <button type="button" class="nt-a11y-chip nt-a11y-chip--accent" data-speech="start"><i class="bi bi-play-fill" aria-hidden="true"></i> Lire</button>
                        <button type="button" class="nt-a11y-chip" data-speech="pause"><i class="bi bi-pause-fill" aria-hidden="true"></i> Pause</button>
                        <button type="button" class="nt-a11y-chip" data-speech="stop"><i class="bi bi-stop-fill" aria-hidden="true"></i> Stop</button>
                    </div>
                    <p id="nt-a11y-speech-status" class="nt-a11y-ctrl__help" role="status">Lecture arrêtée.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="nt-a11y-panel__footer">
        <button type="button" id="nt-a11y-reset" class="nt-a11y-btn nt-a11y-btn--ghost">
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            Réinitialiser
        </button>
        <button type="button" class="nt-a11y-btn nt-a11y-btn--primary" id="nt-a11y-done">
            Fermer
        </button>
    </footer>
</aside>
