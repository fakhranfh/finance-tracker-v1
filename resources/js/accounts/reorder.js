import { closeModal, openModal } from '../shared/modal.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { get, put } from '../shared/http.js';

const ACCOUNTS_PER_LOAD = 100;

const ICON_UP = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75 12 8.25l7.5 7.5" /></svg>';
const ICON_DOWN = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>';

/**
 * Manage Accounts: a modal that lists accounts in their display order and
 * lets the user move each one up or down before saving the new order.
 */
export function createAccountOrder({ openButton, modalId, list, saveButton, routes }) {
    let accounts = [];

    function moveButton(label, icon, disabled, onClick) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'cursor-pointer rounded-md p-1.5 text-on-surface-variant hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40';
        button.title = label;
        button.innerHTML = `${icon}<span class="sr-only"></span>`;
        button.lastChild.textContent = label;
        button.disabled = disabled;
        button.addEventListener('click', onClick);

        return button;
    }

    function render() {
        list.replaceChildren(
            ...accounts.map((account, index) => {
                const item = document.createElement('li');
                item.className = 'flex items-center justify-between gap-3 rounded-lg border border-outline-variant px-3 py-2';

                const name = document.createElement('span');
                name.className = 'min-w-0 truncate font-body text-body-sm text-on-surface';
                name.textContent = account.name;

                const controls = document.createElement('div');
                controls.className = 'flex shrink-0 items-center gap-1';
                controls.append(
                    moveButton(list.dataset.labelMoveUp, ICON_UP, index === 0, () => move(index, index - 1)),
                    moveButton(list.dataset.labelMoveDown, ICON_DOWN, index === accounts.length - 1, () => move(index, index + 1)),
                );

                item.append(name, controls);

                return item;
            }),
        );

        saveButton.disabled = accounts.length < 2;
    }

    function move(from, to) {
        if (to < 0 || to >= accounts.length) {
            return;
        }

        [accounts[from], accounts[to]] = [accounts[to], accounts[from]];
        render();
    }

    async function load() {
        const { ok, data } = await get(`${routes.index}?per_page=${ACCOUNTS_PER_LOAD}`);

        if (ok) {
            accounts = data.data;
            render();
        }
    }

    openButton.addEventListener('click', () => {
        openModal(modalId);
        load();
    });

    saveButton.addEventListener('click', async () => {
        setButtonLoading(saveButton, true);

        try {
            const { ok } = await put(routes.reorder, { ids: accounts.map((account) => account.id) });

            if (ok) {
                closeModal(modalId);
            }
        } finally {
            setButtonLoading(saveButton, false);
        }
    });
}
