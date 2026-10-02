const VISIBLE_FIELDS = 2;

export function applyCardCollapse(row, { expandLabel, collapseLabel }) {
    const fields = [...row.children].filter((cell) => 'label' in cell.dataset);

    if (fields.length <= VISIBLE_FIELDS + 1) {
        return;
    }

    const hidden = fields.slice(VISIBLE_FIELDS);

    hidden.forEach((cell) => {
        cell.dataset.collapsible = '';
    });

    const toggleCell = document.createElement('td');
    toggleCell.className = 'px-4 py-2 text-center sm:hidden';

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'inline-flex cursor-pointer items-center gap-1 text-body-sm font-medium text-primary hover:underline';

    const label = document.createElement('span');
    label.textContent = expandLabel;

    const chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    chevron.setAttribute('viewBox', '0 0 24 24');
    chevron.setAttribute('fill', 'none');
    chevron.setAttribute('stroke', 'currentColor');
    chevron.setAttribute('stroke-width', '2');
    chevron.setAttribute('aria-hidden', 'true');
    chevron.setAttribute('class', 'size-4 transition-transform');
    chevron.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />';

    toggle.append(label, chevron);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', () => {
        const expanded = row.toggleAttribute('data-expanded');

        label.textContent = expanded ? collapseLabel : expandLabel;
        chevron.classList.toggle('rotate-180', expanded);
        toggle.setAttribute('aria-expanded', String(expanded));
    });

    toggleCell.appendChild(toggle);
    hidden[0].before(toggleCell);
}
