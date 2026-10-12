const inputClass = 'w-full rounded-md border border-outline-variant bg-surface px-4 py-2.5 font-body text-body-md text-on-surface focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
const listClass = 'absolute z-20 mt-1 hidden max-h-60 w-full overflow-auto rounded-md border border-outline-variant bg-surface py-1 shadow-lg';
const itemClass = 'cursor-pointer px-4 py-2 font-body text-body-md text-on-surface aria-selected:bg-primary-fixed aria-selected:text-primary';

/**
 * Turns a <select> into a type-to-search combobox. The select stays in the DOM
 * as the source of truth (name, value, validation, `change` events), so callers
 * keep reading and writing it as before; call `refresh()` after changing its
 * value or options (`hidden` options are skipped) from code.
 *
 * @param {HTMLSelectElement} select
 * @returns {{ refresh: () => void }}
 */
export function searchableSelect(select) {
    const wrapper = document.createElement('div');
    const input = document.createElement('input');
    const list = document.createElement('ul');
    let matches = [];
    let active = -1;

    wrapper.className = 'relative';
    input.type = 'text';
    // A distinct id keeps `form.elements.namedItem(name)` resolving to the select alone.
    input.id = `${select.id}-search`;
    input.className = inputClass;
    input.autocomplete = 'off';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', `${select.id}-listbox`);
    list.id = `${select.id}-listbox`;
    list.className = listClass;
    list.setAttribute('role', 'listbox');

    document.querySelector(`label[for="${select.id}"]`)?.setAttribute('for', input.id);
    select.removeAttribute('id');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.classList.add('sr-only');
    select.before(wrapper);
    wrapper.append(input, list, select);

    const isOpen = () => !list.classList.contains('hidden');

    function setOpen(open) {
        list.classList.toggle('hidden', !open);
        input.setAttribute('aria-expanded', String(open));
    }

    function refresh() {
        const selected = select.selectedOptions[0];

        input.value = selected?.value ? selected.textContent : '';
        input.placeholder = select.querySelector('option[value=""]')?.textContent ?? '';
    }

    function highlight(index) {
        active = index;
        [...list.children].forEach((item, i) => item.setAttribute('aria-selected', String(i === index)));
        list.children[index]?.scrollIntoView({ block: 'nearest' });
    }

    function choose(option) {
        const changed = select.value !== option.value;

        select.value = option.value;
        refresh();
        setOpen(false);
        if (changed) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function render(query = '') {
        const needle = query.trim().toLowerCase();

        matches = [...select.options].filter((option) => !option.hidden
            && (needle === '' || (option.value !== '' && option.textContent.toLowerCase().includes(needle))));
        list.replaceChildren(...matches.map((option) => {
            const item = document.createElement('li');

            item.className = itemClass;
            item.setAttribute('role', 'option');
            item.textContent = option.textContent;
            // mousedown, not click: it fires before the input's blur closes the list.
            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
                choose(option);
            });

            return item;
        }));
        highlight(matches.findIndex((option) => option.value === select.value));
        setOpen(true);
    }

    input.addEventListener('focus', () => {
        input.select();
        render();
    });
    input.addEventListener('input', () => render(input.value));
    input.addEventListener('blur', () => {
        setOpen(false);
        refresh();
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!isOpen()) {
                render(input.value);
            }
            const count = matches.length;
            highlight(count === 0 ? -1 : (active + (event.key === 'ArrowDown' ? 1 : -1) + count) % count);
        } else if (event.key === 'Enter' && isOpen()) {
            event.preventDefault();
            if (matches[active]) {
                choose(matches[active]);
            }
        } else if (event.key === 'Escape') {
            setOpen(false);
            refresh();
        }
    });

    refresh();

    return { refresh };
}
