import { wireMoneyInputs } from '../shared/money-input.js';
import { formatMoney } from '../shared/money.js';

const FIELDS = ['id', 'name', 'amount', 'due_date', 'note'];

/**
 * The "Sub debts" repeater of the debt form: one row per instalment, edited together with the debt.
 * With the "sub debts" mode on, the amount input follows the rows (their sum). In "amount" mode the field is
 * hidden and an empty list is sent. What was paid is not typed in: it comes from the records that pay the
 * debt or sub debt off.
 * config: { form }
 * returns: { add, fill, load, markUnloaded, payload }
 */
export function createSubDebtsField({ form }) {
    const root = form.querySelector('[data-sub-debts-field]');
    const rowsNode = root.querySelector('[data-sub-debts-rows]');
    const template = root.querySelector('template');
    const totals = root.querySelector('[data-sub-debts-totals]');
    const amountInput = form.elements.namedItem('amount');
    const dueDateInput = form.elements.namedItem('due_date');
    const modeInputs = [...form.querySelectorAll('[name="debt_mode"]')];

    let loaded = true;
    // Without the mode radios (the standalone sub debts page) the field is always on.
    const standalone = modeInputs.length === 0;
    let useSubDebts = standalone;

    const rows = () => [...rowsNode.querySelectorAll('[data-sub-debt-row]')];
    const input = (row, name) => row.querySelector(`[name$=".${name}"]`);
    const sum = (name) => rows().reduce((total, row) => total + (Number(input(row, name).value) || 0), 0);

    /** Keeps the field names, ids and error slots of every row in step with its position, so server errors land on the right row. */
    function renumber() {
        rows().forEach((row, index) => {
            row.querySelectorAll('[name],[id],[for],[data-error-for]').forEach((node) => {
                ['name', 'id', 'for', 'data-error-for'].forEach((attribute) => {
                    if (node.hasAttribute(attribute)) {
                        node.setAttribute(attribute, node.getAttribute(attribute).replace(/sub_debts\.(\d+|__INDEX__)\./, `sub_debts.${index}.`));
                    }
                });
            });
        });
    }

    /** Refreshes the totals line, the debt's own amount and the rows that follow the debt's due date. */
    function sync() {
        const items = rows();
        const follow = useSubDebts && items.length > 0;

        amountInput.readOnly = follow;

        if (follow) {
            amountInput.value = String(sum('amount'));
        }

        items.forEach((row) => {
            const same = input(row, 'same_due_date');
            const date = input(row, 'due_date');

            same.disabled = !dueDateInput.value;

            const following = same.checked && !same.disabled;

            date.disabled = following;

            if (following) {
                date.value = dueDateInput.value;
            }
        });

        totals.classList.toggle('hidden', items.length === 0);
        totals.textContent = root.dataset.totalsTemplate.replace(':amount', formatMoney(sum('amount')));
    }

    function add(values = {}) {
        const row = template.content.firstElementChild.cloneNode(true);

        rowsNode.append(row);
        renumber();
        wireMoneyInputs(row);

        FIELDS.forEach((name) => {
            input(row, name).value = values[name] ?? '';
        });

        // A new sub debt starts out due on the same day as its debt; an existing one only when its date already matches.
        input(row, 'same_due_date').checked = 'id' in values || 'due_date' in values ? Boolean(values.due_date) && values.due_date === dueDateInput.value : true;

        sync();

        return row;
    }

    /** Switches between a plain amount and an amount made of the sub debts' amounts. */
    function setMode(subDebts) {
        if (standalone) {
            sync();

            return;
        }

        useSubDebts = subDebts;
        modeInputs.forEach((node) => {
            node.checked = node.value === (subDebts ? 'sub_debts' : 'amount');
        });
        root.classList.toggle('hidden', !subDebts);

        if (subDebts && rows().length === 0) {
            add();
        }

        sync();
    }

    function fill(items) {
        rowsNode.replaceChildren();
        items.forEach((item) => add(item));
        setMode(items.length > 0);
    }

    modeInputs.forEach((node) => node.addEventListener('change', () => setMode(node.value === 'sub_debts' && node.checked)));
    setMode(false);

    root.querySelector('[data-sub-debt-add]').addEventListener('click', () => {
        add().querySelector('input[name$=".name"]').focus();
    });

    rowsNode.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-sub-debt-remove]');

        if (remove) {
            remove.closest('[data-sub-debt-row]').remove();
            renumber();
            sync();
        }
    });

    root.addEventListener('input', sync);
    dueDateInput.addEventListener('input', sync);

    return {
        add,
        fill,
        /** Edit mode: shows the debt's existing sub debts. */
        load: fill,
        /** Edit mode when the sub debts could not be loaded: they are left out of the payload so they are not removed. */
        markUnloaded() {
            loaded = false;
            root.classList.add('hidden');
            modeInputs.forEach((node) => {
                node.disabled = true;
            });
        },
        /** The `sub_debts` value of the request, or undefined to leave the debt's sub debts as they are. */
        payload() {
            if (!loaded) {
                return undefined;
            }

            if (!useSubDebts) {
                return [];
            }

            return rows().map((row) => ({
                id: input(row, 'id').value ? Number(input(row, 'id').value) : undefined,
                name: input(row, 'name').value,
                amount: input(row, 'amount').value,
                due_date: input(row, 'due_date').value || null,
                note: input(row, 'note').value || null,
            }));
        },
    };
}
