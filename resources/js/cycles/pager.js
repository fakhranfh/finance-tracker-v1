import { renderPagination } from '../shared/table/table-pagination.js';

/**
 * Pager state for a list the API pages: it draws the page buttons into every `pagination` container
 * (above and below the list) from the `meta` of a response, and keeps the per-page `<select>`s (above and
 * below the list) in step. `onChange` loads the page again after a button or a select changed it.
 */
export function createPager({ pagination, selects, onChange }) {
    let page = 1;
    let perPage = Number(selects[0].value);

    const navigate = (url) => {
        page = Number(new URL(url, window.location.origin).searchParams.get('page'));
        onChange();
        pagination[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    selects.forEach((select) =>
        select.addEventListener('change', () => {
            perPage = Number(select.value);
            page = 1;
            selects.forEach((other) => {
                other.value = select.value;
            });
            onChange();
        }),
    );

    return {
        get page() {
            return page;
        },

        get perPage() {
            return perPage;
        },

        reset() {
            page = 1;
        },

        /** Draws the buttons for the page the API returned (its `meta`). */
        render(meta) {
            const smallest = Math.min(...Array.from(selects[0].options, (option) => Number(option.value)));

            page = meta.current_page;
            renderPagination(pagination, meta, navigate);

            // Nothing to choose or page through while everything fits on the smallest page size.
            selects.forEach((select) => select.closest('[data-pager-bar]').classList.toggle('!hidden', meta.total <= smallest));
        },
    };
}
