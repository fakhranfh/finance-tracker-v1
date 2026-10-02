/**
 * Column sorting for tables: header buttons ([data-sort-key]) cycle
 * asc -> desc -> none, the mobile modal inputs ([data-modal-sort-key] /
 * [data-modal-sort-direction]) set it directly. `onChange()` fires after
 * every change so the caller can reload.
 */
export function createTableSort({ root, onChange }) {
    let sort = null;
    let direction = 'asc';

    root?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sort-key]');

        if (!button) {
            return;
        }

        const key = button.dataset.sortKey;

        if (sort === key && direction === 'desc') {
            sort = null;
            direction = 'asc';
        } else if (sort === key) {
            direction = 'desc';
        } else {
            sort = key;
            direction = 'asc';
        }

        onChange();
    });

    document
        .querySelectorAll('[data-modal-sort-key], [data-modal-sort-direction]')
        .forEach((input) =>
            input.addEventListener('change', () => {
                sort = document.querySelector('[data-modal-sort-key]:checked')?.value || null;
                direction = document.querySelector('[data-modal-sort-direction]:checked')?.value || 'asc';

                onChange();
            }),
        );

    return {
        applyToUrl(url) {
            if (sort) {
                url.searchParams.set('sort', sort);
                url.searchParams.set('direction', direction);
            }
        },

        updateIcons() {
            root?.querySelectorAll('[data-sort-icon]').forEach((icon) => {
                if (icon.dataset.sortIcon !== sort) {
                    icon.dataset.direction = '';
                    icon.classList.add('invisible');
                    icon.style.transform = '';
                    return;
                }

                icon.dataset.direction = direction;
                icon.classList.remove('invisible');
                icon.classList.add('text-primary');
                icon.style.transform = direction === 'desc' ? 'rotate(180deg)' : '';
            });
        },
    };
}
