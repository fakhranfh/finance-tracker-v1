import { closeModal, openModal } from '../shared/modal.js';
import { clearFieldErrors, showFieldErrors, showGenericError } from '../shared/field-errors.js';
import { hideSkeleton, setButtonLoading } from '../shared/loading-state.js';
import { get, put } from '../shared/http.js';
import { formatMoney } from '../shared/money.js';

const ACCOUNTS_PER_PAGE = 100;

/**
 * Dashboard account cards: lists accounts as colored tiles and lets the user
 * change each account's color and icon from a modal.
 */
export function createAccountCards({ ids, routes }) {
    const root = document.getElementById(ids.root);
    const list = document.getElementById(ids.list);
    const modal = document.getElementById(ids.modal);
    const form = document.getElementById(ids.form);

    const cardTemplate = root.querySelector('[data-account-card-template]');
    const addTemplate = root.querySelector('[data-account-card-add]');

    let currentAccount = null;

    modal.querySelectorAll('[data-swatch]').forEach((swatch) => {
        swatch.style.backgroundColor = swatch.dataset.swatch;
    });

    function iconMarkup(icon) {
        return root.querySelector(`[data-icon-template="${icon}"]`)?.innerHTML ?? '';
    }

    function buildCard(account) {
        const fragment = cardTemplate.content.cloneNode(true);
        const card = fragment.querySelector('[data-account-card]');

        card.dataset.accountId = account.id;
        card.style.backgroundColor = account.color;
        fragment.querySelector('[data-account-card-icon]').innerHTML = iconMarkup(account.icon);
        fragment.querySelector('[data-account-card-name]').textContent = account.name;
        fragment.querySelector('[data-account-card-balance]').textContent = formatMoney(account.balance);
        fragment.querySelector('[data-account-card-edit]').addEventListener('click', () => openAppearance(account));

        return fragment;
    }

    function renderAccounts(accounts) {
        list.replaceChildren(
            ...accounts.map((account) => buildCard(account)),
            addTemplate.content.cloneNode(true),
        );
    }

    function openAppearance(account) {
        currentAccount = account;
        clearFieldErrors(form);
        form.elements.namedItem('color').value = account.color;
        form.elements.namedItem('icon').value = account.icon;
        openModal(ids.modal);
    }

    async function loadAccounts() {
        const { ok, data } = await get(`${routes.index}?per_page=${ACCOUNTS_PER_PAGE}`);

        if (ok) {
            renderAccounts(data.data);
        }

        hideSkeleton(ids.list, ids.skeleton);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearFieldErrors(form);

        const submitButton = form.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        try {
            const { ok, status, data } = await put(routes.update(currentAccount.id), {
                name: currentAccount.name,
                balance: currentAccount.balance,
                color: form.elements.namedItem('color').value || currentAccount.color,
                icon: form.elements.namedItem('icon').value || currentAccount.icon,
            });

            if (ok) {
                const updatedAccount = data.data;
                list.querySelector(`[data-account-id="${updatedAccount.id}"]`)?.replaceWith(buildCard(updatedAccount));
                closeModal(ids.modal);
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'color');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'color');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });

    loadAccounts();
}
