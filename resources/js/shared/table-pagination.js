const ICON_PREVIOUS =
    '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>';
const ICON_NEXT =
    '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>';
const ICON_FIRST =
    '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15.79 14.77a.75.75 0 01-1.06.02l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 111.04 1.08L12.168 10l3.62 3.71a.75.75 0 010 1.06zm-6 0a.75.75 0 01-1.06.02l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 111.04 1.08L6.168 10l3.62 3.71a.75.75 0 010 1.06z" clip-rule="evenodd"/></svg>';
const ICON_LAST =
    '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15.79 14.77a.75.75 0 01-1.06.02l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 111.04 1.08L12.168 10l3.62 3.71a.75.75 0 010 1.06zm-6 0a.75.75 0 01-1.06.02l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 111.04 1.08L6.168 10l3.62 3.71a.75.75 0 010 1.06z" clip-rule="evenodd" transform="matrix(-1 0 0 1 20 0)"/></svg>';

function pageUrl(links, page) {
    const base = links.find((link) => link.url)?.url;

    if (!base) {
        return null;
    }

    const url = new URL(base, window.location.origin);
    url.searchParams.set('page', page);

    return url.toString();
}

/**
 * Render pagination buttons (first, previous, pages, next, last) into every
 * container. `onNavigate(url)` is called when a button is clicked.
 */
export function renderPagination(containers, meta, onNavigate) {
    if (!meta) {
        return;
    }

    const edgeButton = (label, icon, page) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'min-h-10 min-w-10 cursor-pointer rounded-md border border-outline-variant px-3 py-1.5 text-on-surface-variant hover:bg-surface-container';
        button.setAttribute('aria-label', label);
        button.innerHTML = icon;
        button.addEventListener('click', () => onNavigate(pageUrl(meta.links, page)));

        return button;
    };

    containers.forEach((pagination) => {
        pagination.innerHTML = '';

        if (meta.last_page <= 1) {
            return;
        }

        if (meta.current_page > 1) {
            pagination.appendChild(edgeButton('First page', ICON_FIRST, 1));
        }

        meta.links?.forEach((link, index, links) => {
            if (!link.url) {
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = `min-h-10 min-w-10 cursor-pointer rounded-md px-3 py-1.5 ${link.active ? 'bg-primary text-on-primary' : 'border border-outline-variant text-on-surface-variant hover:bg-surface-container'}`;

            if (index === 0 || index === links.length - 1) {
                button.setAttribute('aria-label', link.label.replace(/&[a-z]+;/g, '').trim());
                button.innerHTML = index === 0 ? ICON_PREVIOUS : ICON_NEXT;
            } else {
                button.innerHTML = link.label;
            }

            button.addEventListener('click', () => onNavigate(link.url));

            pagination.appendChild(button);
        });

        if (meta.current_page < meta.last_page) {
            pagination.appendChild(edgeButton('Last page', ICON_LAST, meta.last_page));
        }
    });
}
