import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { routes } from '../routes.js';

const table = document.getElementById('categories-table');
const typeLabels = JSON.parse(table?.dataset.typeLabels ?? '{}');
const icons = JSON.parse(table?.dataset.icons ?? '{}');

function iconNode(name, color) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.5');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('class', 'size-6');
    svg.style.color = color;
    svg.innerHTML = icons[name] ?? '';

    return svg;
}

function colorNode(color) {
    const wrapper = document.createElement('span');
    wrapper.className = 'inline-flex items-center gap-2';

    const swatch = document.createElement('span');
    swatch.className = 'size-4 rounded-full border border-outline-variant';
    swatch.style.backgroundColor = color;

    const hex = document.createElement('span');
    hex.textContent = color.toUpperCase();

    wrapper.append(swatch, hex);

    return wrapper;
}

createTable({
    ids: {
        table: 'categories-table',
        wrapper: 'categories-table-wrapper',
        toolbar: 'categories-table-toolbar',
        generateButton: 'generate-categories',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-category',
        confirmBulkDelete: 'confirm-bulk-delete-categories',
    },
    flashKey: 'categories-flash-success',
    selectionKey: 'categories-table-selection',
    deleteModalId: 'delete-category-modal',
    bulkDeleteModalId: 'bulk-delete-categories-modal',
    defaultSort: { key: 'name', direction: 'asc' },
    routes: {
        index: routes.categories.index,
        destroy: routes.categories.destroy,
        bulkDestroy: routes.categories.bulkDestroy,
        generate: routes.categories.generate,
    },
    row: (item) => ({
        cells: [
            item.name ?? '',
            typeLabels[item.type] ?? item.type ?? '',
            colorNode(item.color ?? ''),
            iconNode(item.icon, item.color),
            formatDateTime(item.created_at),
        ],
        editUrl: routes.pages.categoryEdit(item.id),
        locked: false,
    }),
});
