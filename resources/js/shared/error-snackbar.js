export function scrollToField(form, field) {
    const input = form.querySelector(`[name="${field}"]`) ?? form.querySelector(`[data-error-for="${field}"]`);
    if (input) {
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        input.focus?.({ preventScroll: true });
    }
}

function fieldLabel(form, field) {
    const input = form.querySelector(`[name="${field}"]`);
    const label = input?.id ? form.querySelector(`label[for="${input.id}"]`) : null;

    return label?.textContent.trim() || field;
}

/**
 * Render a clickable error-summary snackbar ([data-error-snackbar] in the
 * layout) listing every error; clicking an item scrolls to its input.
 */
export function showErrorSnackbar(form, entries) {
    const snackbar = document.querySelector('[data-error-snackbar]');
    if (!snackbar) {
        return;
    }

    const list = snackbar.querySelector('[data-error-snackbar-list]') ?? snackbar;
    list.innerHTML = '';

    entries.forEach(([field, messages]) => {
        const item = document.createElement('button');
        item.type = 'button';

        const label = document.createElement('span');
        label.className = 'block font-semibold';
        label.textContent = fieldLabel(form, field);

        const message = document.createElement('span');
        message.className = 'block';
        message.textContent = messages[0];

        item.append(label, message);
        item.addEventListener('click', () => scrollToField(form, field));
        list.appendChild(item);
    });

    snackbar.classList.remove('hidden');
}

export function hideSnackbar() {
    const snackbar = document.querySelector('[data-error-snackbar]');
    snackbar?.classList.add('hidden');
}
