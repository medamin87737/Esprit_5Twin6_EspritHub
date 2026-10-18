(function ($) {
    'use strict';

    $('[data-toggle="tooltip"]').tooltip();

    if (window.innerWidth < 768) {
        $('.sidebar .collapse').removeClass('show');
    }
    if (window.innerWidth < 480) {
        $('body').addClass('sidebar-toggled');
        $('.sidebar').addClass('toggled');
    }

    const $sidebar = $('#accordionSidebar');

    const placeFlyout = function (collapse) {
        if (getComputedStyle(collapse).position !== 'fixed') {
            collapse.style.top = '';
            collapse.style.left = '';
            return;
        }
        const link = collapse.parentElement.querySelector('.nav-link').getBoundingClientRect();
        const maxTop = window.innerHeight - collapse.offsetHeight - 8;
        collapse.style.left = `${$sidebar[0].getBoundingClientRect().right + 8}px`;
        collapse.style.top = `${Math.max(8, Math.min(link.top, maxTop))}px`;
    };

    $sidebar.on('show.bs.collapse shown.bs.collapse', '.collapse', function () {
        placeFlyout(this);
    });

    $sidebar.on('scroll', function () {
        $sidebar.find('.collapse.show').each(function () {
            if (getComputedStyle(this).position === 'fixed') {
                $(this).collapse('hide');
            }
        });
    });

    $(document).on('click', '[data-password-toggle]', function () {
        const input = document.getElementById(this.dataset.passwordToggle);
        if (!input) {
            return;
        }
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        this.setAttribute('aria-pressed', String(!visible));
        this.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        $(this).find('i').toggleClass('bi-eye', visible).toggleClass('bi-eye-slash', !visible);
    });

    const authVideo = document.querySelector('.nt-auth-visual video');
    if (authVideo && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        authVideo.pause();
        authVideo.removeAttribute('autoplay');
    }

    /**
     * Recherche dynamique admin (Module 2 et listes table-card) :
     * à chaque lettre, recharge le corps du tableau sans recharger toute la page.
     */
    (function initLiveSearch() {
        const root = document.querySelector('[data-nt-live-search]');
        if (!root) {
            return;
        }

        const form = root.querySelector('[data-nt-live-form]');
        const input = root.querySelector('[data-nt-live-q]');
        const body = root.querySelector('[data-nt-live-body]');
        const subtitle = root.querySelector('.nt-card-subtitle');
        if (!form || !body) {
            return;
        }

        let timer = null;
        let abort = null;
        let reqId = 0;

        const buildUrl = function () {
            const action = form.getAttribute('action') || window.location.pathname;
            const params = new URLSearchParams(new FormData(form));
            // Page 1 à chaque nouvelle recherche texte / filtre
            params.delete('page');
            const qs = params.toString();
            return qs ? `${action}?${qs}` : action;
        };

        const restoreFocus = function (el, start, end) {
            if (!el) {
                return;
            }
            el.focus({ preventScroll: true });
            try {
                const len = el.value.length;
                el.setSelectionRange(
                    Math.min(start ?? len, len),
                    Math.min(end ?? len, len)
                );
            } catch (e) {
                // input type=search certains navigateurs
            }
        };

        const applyResponse = function (html, url, focusMeta) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextRoot = doc.querySelector('[data-nt-live-search]');
            if (!nextRoot) {
                window.location.href = url;
                return;
            }

            const nextBody = nextRoot.querySelector('[data-nt-live-body]');
            const nextSubtitle = nextRoot.querySelector('.nt-card-subtitle');
            if (nextBody) {
                body.innerHTML = nextBody.innerHTML;
            }
            if (subtitle && nextSubtitle) {
                subtitle.textContent = nextSubtitle.textContent;
            }

            // Resync filtres (options villes / labs) si le HTML les a mis à jour
            const nextForm = nextRoot.querySelector('[data-nt-live-form]');
            if (nextForm) {
                nextForm.querySelectorAll('select[name], input[type="date"][name]').forEach(function (src) {
                    const dest = form.querySelector(`[name="${src.name}"]`);
                    if (!dest || dest === input) {
                        return;
                    }
                    if (dest.tagName === 'SELECT') {
                        dest.innerHTML = src.innerHTML;
                        dest.value = src.value;
                    } else {
                        dest.value = src.value;
                    }
                });
            }

            history.replaceState(history.state, '', url);
            root.classList.remove('is-loading');

            if (focusMeta?.name === 'q') {
                const fresh = root.querySelector('[data-nt-live-q]');
                restoreFocus(fresh, focusMeta.start, focusMeta.end);
            }
        };

        const run = function (focusMeta) {
            const url = buildUrl();
            const id = ++reqId;

            if (abort) {
                abort.abort();
            }
            abort = new AbortController();
            root.classList.add('is-loading');

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                signal: abort.signal,
                credentials: 'same-origin',
            })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('Recherche indisponible');
                    }
                    return res.text();
                })
                .then(function (html) {
                    if (id !== reqId) {
                        return;
                    }
                    applyResponse(html, url, focusMeta);
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    root.classList.remove('is-loading');
                    form.submit();
                });
        };

        const schedule = function (delay) {
            clearTimeout(timer);
            const el = root.querySelector('[data-nt-live-q]');
            const focusMeta = el
                ? {
                    name: 'q',
                    start: el.selectionStart,
                    end: el.selectionEnd,
                }
                : null;
            timer = setTimeout(function () {
                run(focusMeta);
            }, delay);
        };

        if (input) {
            input.addEventListener('input', function () {
                schedule(280);
            });
        }

        form.querySelectorAll('select, input[type="date"]').forEach(function (el) {
            el.addEventListener('change', function () {
                schedule(0);
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(timer);
            run({ name: 'q' });
        });
    })();
})(jQuery);
