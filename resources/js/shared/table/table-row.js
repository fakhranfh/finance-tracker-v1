import { applyCardCollapse } from './table-card.js';

const ICON_EDIT =
    '<svg class="h-5 w-5 sm:hidden" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>';
const ICON_VIEW =
    '<svg class="h-5 w-5 sm:hidden" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>';
const ICON_DELETE =
    '<svg class="h-5 w-5 sm:hidden" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';

/**
 * Builds table rows: select checkbox, data cells, edit/delete actions and the
 * mobile card collapse. Labels are read from the tbody's data-label-* attributes.
 *
 * render({ id, cells, editUrl, onDelete, locked }):
 *   cells    text (or a DOM node, e.g. an icon) for each data column (first is emphasised)
 *   locked   row can't be selected or deleted (e.g. the current user)
 *   onEdit   edit button handler, used instead of the editUrl link (e.g. to open a modal)
 *   readOnly row has no edit or delete action at all
 *   noEdit   row has no edit action but can still be deleted
 */
export function createTableRow({ tbody, selection, selectAll }) {
    const labels = tbody.dataset;

    return {
        render({ id, cells, viewUrl = null, editUrl, onEdit = null, onDelete, locked = false, readOnly = false, noEdit = false }) {
            const row = document.createElement('tr');

            // Without a checkbox column (table selectable="false") the leading cell is left out.
            const selectable = tbody.dataset.selectable !== 'false';
            const offset = selectable ? 1 : 0;

            row.innerHTML = [
                selectable ? '<td class="w-10 px-4 py-3"></td>' : '',
                ...cells.map(
                    (_, index) =>
                        `<td class="px-4 py-3 ${index === 0 ? 'text-on-surface' : 'text-on-surface-variant'}"></td>`,
                ),
                '<td class="px-4 py-3"></td>',
            ].join('');

            const headerLabels = [...(tbody.closest('table')?.querySelectorAll('thead th') ?? [])].map((th) =>
                th.textContent.trim(),
            );

            cells.forEach((value, index) => {
                const cell = row.children[index + offset];
                cell.dataset.label = headerLabels[index + offset] ?? '';
                if (value instanceof Node) {
                    cell.replaceChildren(value);
                } else {
                    cell.textContent = value;
                }
                // Mobile: the action icons sit in a band above the first value, so push that value below them.
                // Margin, not padding: the table's mobile styles already set the cell's padding.
                if (index === 0) {
                    cell.classList.add('max-sm:mt-10');
                }
            });

            if (selectable) {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = id;
                checkbox.className =
                    'h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20';

                if (locked) {
                    checkbox.disabled = true;
                    checkbox.className += ' cursor-not-allowed opacity-40';
                } else {
                    checkbox.dataset.rowSelect = '';
                    checkbox.checked = selection.has(id);
                    checkbox.addEventListener('change', () => {
                        selection.toggle(id, checkbox.checked);
                        selectAll.onRowToggle();
                    });
                }
                row.children[0].appendChild(checkbox);
            }

            const actions = document.createElement('div');
            actions.className = 'flex items-center justify-end gap-3';

            if (viewUrl) {
                const viewLink = document.createElement('a');
                viewLink.href = viewUrl;
                viewLink.className = 'inline-flex items-center font-medium text-primary hover:underline';
                viewLink.title = labels.labelView;
                viewLink.innerHTML = `${ICON_VIEW}<span class="max-sm:sr-only"></span>`;
                viewLink.lastChild.textContent = labels.labelView;
                actions.append(viewLink);
            }

            if (!readOnly && !noEdit) {
                const editLink = document.createElement(onEdit ? 'button' : 'a');
                editLink.className = 'inline-flex items-center font-medium text-primary hover:underline';
                editLink.title = labels.labelEdit;
                editLink.innerHTML = `${ICON_EDIT}<span class="max-sm:sr-only"></span>`;
                editLink.lastChild.textContent = labels.labelEdit;

                if (onEdit) {
                    editLink.type = 'button';
                    editLink.classList.add('cursor-pointer');
                    editLink.addEventListener('click', onEdit);
                } else {
                    editLink.href = editUrl;
                }

                actions.append(editLink);
            }

            if (!locked && !readOnly) {
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'inline-flex cursor-pointer items-center font-medium text-error hover:underline';
                deleteButton.title = labels.labelDelete;
                deleteButton.innerHTML = `${ICON_DELETE}<span class="max-sm:sr-only"></span>`;
                deleteButton.lastChild.textContent = labels.labelDelete;
                deleteButton.addEventListener('click', onDelete);
                actions.append(deleteButton);
            }

            row.lastElementChild.className += ' max-sm:absolute max-sm:right-2 max-sm:top-1 max-sm:py-2';
            row.lastElementChild.appendChild(actions);

            applyCardCollapse(row, {
                expandLabel: labels.labelExpand,
                collapseLabel: labels.labelCollapse,
            });

            return row;
        },
    };
}
