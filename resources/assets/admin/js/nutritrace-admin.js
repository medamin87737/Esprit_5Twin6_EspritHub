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
})(jQuery);
