const openers = new Map();

export function openModal(id) {
    const modal = document.getElementById(id);

    if (!modal) {
        return;
    }

    openers.set(id, document.activeElement);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    // Land on the safe action (Cancel) so a stray Enter never confirms a delete.
    (modal.querySelector('[data-modal-close]') ?? modal).focus?.();
}

export function closeModal(id) {
    const modal = document.getElementById(id);

    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
    openers.get(id)?.focus?.();
    openers.delete(id);
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-modal]:not(.hidden)').forEach((modal) => closeModal(modal.id));
});

document.addEventListener('click', (event) => {
    const closeTrigger = event.target.closest('[data-modal-close]');
    if (closeTrigger) {
        closeModal(closeTrigger.closest('[data-modal]')?.id);
        return;
    }

    if (event.target.closest('[data-modal]') === event.target) {
        closeModal(event.target.id);
    }
});
