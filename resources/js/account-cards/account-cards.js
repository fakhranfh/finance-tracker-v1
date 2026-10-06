import { closeModal, openModal } from '../shared/modal.js';
import { clearFieldErrors, showFieldErrors, showGenericError } from '../shared/field-errors.js';
import { hideSkeleton, setButtonLoading } from '../shared/loading-state.js';
import { get, put } from '../shared/http.js';
import { formatMoney } from '../shared/money.js';

const ACCOUNTS_PER_PAGE = 15;

/**
 * Dashboard account cards: lists accounts as colored tiles, lets the user
 * change each account's color and icon from a modal, and lets them arrange
 * the order the cards appear in.
 */
export function createAccountCards({ ids, routes }) {
    const root = document.getElementById(ids.root);
    const list = document.getElementById(ids.list);
    const modal = document.getElementById(ids.modal);
    const form = document.getElementById(ids.form);

    const cardTemplate = root.querySelector('[data-account-card-template]');
    const addTemplate = root.querySelector('[data-account-card-add]');

    const moreWrapper = root.querySelector('[data-account-cards-more]');
    const toolbar = root.querySelector('[data-account-cards-toolbar]');
    const reorderButton = root.querySelector('[data-account-cards-reorder]');

    let currentAccount = null;
    let allAccounts = [];
    let totalAccounts = 0;
    let reorderMode = false;

    function iconMarkup(icon) {
        return root.querySelector(`[data-icon-template="${icon}"]`)?.innerHTML ?? '';
    }

    function buildCard(account, index) {
        const fragment = cardTemplate.content.cloneNode(true);
        const card = fragment.querySelector('[data-account-card]');
        const moveControls = fragment.querySelector('[data-account-card-move]');
        const moveLeft = fragment.querySelector('[data-account-card-move-left]');
        const moveRight = fragment.querySelector('[data-account-card-move-right]');

        card.dataset.accountId = account.id;
        card.style.backgroundColor = account.color;
        fragment.querySelector('[data-account-card-icon]').innerHTML = iconMarkup(account.icon);
        fragment.querySelector('[data-account-card-name]').textContent = account.name;
        fragment.querySelector('[data-account-card-balance]').textContent = formatMoney(account.balance);
        fragment.querySelector('[data-account-card-edit]').addEventListener('click', () => openAppearance(account));

        moveControls.classList.toggle('hidden', !reorderMode);
        moveControls.classList.toggle('flex', reorderMode);
        moveLeft.disabled = index === 0;
        moveRight.disabled = index === allAccounts.length - 1;
        moveLeft.addEventListener('click', () => moveAccount(index, index - 1));
        moveRight.addEventListener('click', () => moveAccount(index, index + 1));

        return fragment;
    }

    function renderAccounts() {
        list.replaceChildren(
            ...allAccounts.map((account, index) => buildCard(account, index)),
            addTemplate.content.cloneNode(true),
        );

        moreWrapper.classList.toggle('hidden', totalAccounts <= ACCOUNTS_PER_PAGE);
        moreWrapper.classList.toggle('flex', totalAccounts > ACCOUNTS_PER_PAGE);
        toolbar.classList.toggle('hidden', allAccounts.length < 2);
        toolbar.classList.toggle('flex', allAccounts.length >= 2);
    }

    async function moveAccount(from, to) {
        if (to < 0 || to >= allAccounts.length) {
            return;
        }

        [allAccounts[from], allAccounts[to]] = [allAccounts[to], allAccounts[from]];
        renderAccounts();

        const { ok } = await put(routes.reorder, { ids: allAccounts.map((account) => account.id) });

        if (!ok) {
            await loadAccounts();
        }
    }

    reorderButton.addEventListener('click', () => {
        reorderMode = !reorderMode;
        reorderButton.textContent = reorderMode ? reorderButton.dataset.doneLabel : reorderButton.dataset.startLabel;
        renderAccounts();
    });

    reorderButton.dataset.startLabel = reorderButton.textContent.trim();

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
            allAccounts = data.data;
            totalAccounts = data.meta?.total ?? allAccounts.length;
            renderAccounts();
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
                allAccounts = allAccounts.map((account) => (account.id === updatedAccount.id ? updatedAccount : account));
                renderAccounts();
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
