document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');

    if (!toggle) {
        return;
    }

    const input = toggle.parentElement.querySelector('input');
    const reveal = input.type === 'password';

    input.type = reveal ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(reveal));
    toggle.setAttribute('aria-label', reveal ? toggle.dataset.labelHide : toggle.dataset.labelShow);
    toggle.querySelector('[data-icon-show]').classList.toggle('hidden', reveal);
    toggle.querySelector('[data-icon-hide]').classList.toggle('hidden', !reveal);
});
