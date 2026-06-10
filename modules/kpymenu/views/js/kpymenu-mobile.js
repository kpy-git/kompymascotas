document.addEventListener('DOMContentLoaded', () => {
    const btnMenu = document.querySelector('.btn-hamburger');
    const firstLevel = document.querySelector('.kpy-menu-mobile__navigation:has(.first-level)');

    btnMenu.addEventListener('click', () => {
        btnMenu.classList.toggle('hamburger-on');
        btnMenu.classList.toggle('hamburger-off');

        document.querySelector('.kpy-menu-mobile').classList.toggle('active', !btnMenu.classList.contains('hamburger-off'))

        document.querySelectorAll('.kpy-menu-mobile__navigation:has(.second-level)').forEach(el => el.ariaHidden = "true");
        firstLevel.ariaHidden = 'false';
    });

    document.querySelectorAll('.kpy-menu-mobile__element[data-category]').forEach(el => {
        el.addEventListener('click', () => {
            el.closest('[aria-hidden]').ariaHidden = 'true';
            document.querySelector(`.kpy-menu-mobile__navigation:has(.second-level[data-parent="${el.dataset.category}"])`).ariaHidden = 'false';
        });
    });

    document.querySelectorAll('.kpy-menu-mobile__btn-back').forEach(el => {
        el.addEventListener('click', () => {
            el.closest('[aria-hidden]').ariaHidden = 'true';
            document.querySelector(`.kpy-menu-mobile__element[data-category="${el.dataset.parent}"]`).closest('[aria-hidden]').ariaHidden = 'false'
        });
    });
});