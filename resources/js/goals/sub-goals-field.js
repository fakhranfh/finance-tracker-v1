import { wireMoneyInputs } from '../shared/money-input.js';
import { formatMoney } from '../shared/money.js';

const FIELDS = ['id', 'name', 'target_amount', 'saved_amount', 'desired_date', 'note'];

/**
 * The "Sub goals" repeater of the goal form: one row per sub goal, edited together with the goal.
 * The rows must add up to the goal's target amount; their saved amounts add up to the goal's saved amount,
 * so with rows present the goal's saved amount field follows them. Existing rows keep their saved amount
 * (it follows the savings), only new rows can be given one.
 *
 * config: { form }
 * returns: { add, fill, load, markUnloaded, payload, check }
 */
export function createSubGoalsField({ form }) {
    const root = form.querySelector('[data-sub-goals-field]');
    const rowsNode = root.querySelector('[data-sub-goals-rows]');
    const template = root.querySelector('template');
    const totals = root.querySelector('[data-sub-goals-totals]');
    const targetInput = form.elements.namedItem('target_amount');
    const savedInput = form.elements.namedItem('saved_amount');
    const dateInput = form.elements.namedItem('desired_date');

    let loaded = true;

    const rows = () => [...rowsNode.querySelectorAll('[data-sub-goal-row]')];
    const input = (row, name) => row.querySelector(`[name$=".${name}"]`);
    const sum = (name) => rows().reduce((total, row) => total + (Number(input(row, name).value) || 0), 0);
    const cents = (value) => Math.round(value * 100);

    /** Keeps the field names, ids and error slots of every row in step with its position, so server errors land on the right row. */
    function renumber() {
        rows().forEach((row, index) => {
            row.querySelectorAll('[name],[id],[for],[data-error-for]').forEach((node) => {
                ['name', 'id', 'for', 'data-error-for'].forEach((attribute) => {
                    if (node.hasAttribute(attribute)) {
                        node.setAttribute(attribute, node.getAttribute(attribute).replace(/sub_goals\.(\d+|__INDEX__)\./, `sub_goals.${index}.`));
                    }
                });
            });
        });
    }

    /** Refreshes the totals line, the goal's saved amount and the latest date a row may have. */
    function sync() {
        const items = rows();

        savedInput.readOnly = items.length > 0;

        if (items.length > 0) {
            savedInput.value = String(sum('saved_amount'));
        }

        items.forEach((row) => {
            input(row, 'desired_date').max = dateInput.value;
        });

        totals.classList.toggle('hidden', items.length === 0);
        totals.classList.toggle('text-error', cents(sum('target_amount')) !== cents(Number(targetInput.value) || 0));
        totals.classList.toggle('text-on-surface-variant', cents(sum('target_amount')) === cents(Number(targetInput.value) || 0));
        totals.textContent = root.dataset.totalsTemplate
            .replace(':total', formatMoney(sum('target_amount')))
            .replace(':target', formatMoney(Number(targetInput.value) || 0));
    }

    function add(values = {}) {
        const row = template.content.firstElementChild.cloneNode(true);

        rowsNode.append(row);
        renumber();
        wireMoneyInputs(row);

        FIELDS.forEach((name) => {
            input(row, name).value = values[name] ?? (name === 'saved_amount' ? '0' : '');
        });
        input(row, 'saved_amount').readOnly = Boolean(values.id);

        sync();

        return row;
    }

    function fill(items) {
        rowsNode.replaceChildren();
        items.forEach((item) => add(item));
        sync();
    }

    root.querySelector('[data-sub-goal-add]').addEventListener('click', () => {
        add().querySelector('input[name$=".name"]').focus();
    });

    rowsNode.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-sub-goal-remove]');

        if (remove) {
            remove.closest('[data-sub-goal-row]').remove();
            renumber();
            sync();
        }
    });

    root.addEventListener('input', sync);
    [targetInput, dateInput].forEach((node) => node.addEventListener('input', sync));

    return {
        add,
        fill,
        /** Edit mode: shows the goal's existing sub goals. */
        load: fill,
        /** Edit mode when the sub goals could not be loaded: they are left out of the payload so they are not removed. */
        markUnloaded() {
            loaded = false;
            root.classList.add('hidden');
        },
        /** The `sub_goals` value of the request, or undefined to leave the goal's sub goals as they are. */
        payload() {
            if (!loaded) {
                return undefined;
            }

            return rows().map((row) => ({
                id: input(row, 'id').value ? Number(input(row, 'id').value) : undefined,
                name: input(row, 'name').value,
                target_amount: input(row, 'target_amount').value,
                saved_amount: input(row, 'saved_amount').value || '0',
                desired_date: input(row, 'desired_date').value || null,
                note: input(row, 'note').value || null,
            }));
        },
        /** Client-side check that the sub goal targets add up to the goal's target; returns an errors map for showFieldErrors. */
        check() {
            if (rows().length === 0 || cents(sum('target_amount')) === cents(Number(targetInput.value) || 0)) {
                return {};
            }

            return {
                sub_goals: [
                    root.dataset.mismatchMessage
                        .replace(':total', formatMoney(sum('target_amount')))
                        .replace(':target', formatMoney(Number(targetInput.value) || 0)),
                ],
            };
        },
    };
}
