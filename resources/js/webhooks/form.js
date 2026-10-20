import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, getAll, post, put } from '../shared/http.js';
import { wireCopyButtons } from '../shared/copy-to-clipboard.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { webhookFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('webhook-form');
const itemId = form?.dataset.itemId;

wireDevTools(form, webhookFormFixture);
wireCopyButtons(form);
wireToggleAll();
wireIpList();

const ipPanel = form?.querySelector('#allowed-ips-panel');
const ipList = form?.querySelector('#allowed-ips-list');
const ipRowTemplate = form?.querySelector('#allowed-ip-row-template');

function ipInputs() {
    return [...ipList.querySelectorAll('[data-ip-input]')];
}

function refreshIpClear() {
    form.querySelector('[data-ip-clear]').classList.toggle('hidden', ipInputs().length === 0);
}

function addIpRow(value = '') {
    const row = ipRowTemplate.content.firstElementChild.cloneNode(true);
    const input = row.querySelector('[data-ip-input]');

    input.value = value;
    row.querySelector('[data-ip-delete]').addEventListener('click', () => {
        row.remove();
        refreshIpClear();
    });
    ipList.append(row);
    refreshIpClear();

    return input;
}

function setIpMode(mode) {
    form.querySelector(`[name="ip_mode"][value="${mode}"]`).checked = true;
    ipPanel.classList.toggle('hidden', mode !== 'only');

    if (mode === 'only' && ipInputs().length === 0) {
        addIpRow();
    }
}

function wireIpList() {
    form.querySelectorAll('[name="ip_mode"]').forEach((radio) => {
        radio.addEventListener('change', () => setIpMode(radio.value));
    });
    form.querySelector('[data-ip-add]').addEventListener('click', () => addIpRow().focus());
    form.querySelector('[data-ip-clear]').addEventListener('click', () => {
        ipList.replaceChildren();
        refreshIpClear();
    });
}

/** The entered IPs (blank rows ignored) when "allow only" is chosen, otherwise none (allow all). */
function enteredIps() {
    const mode = form.querySelector('[name="ip_mode"]:checked').value;

    return mode === 'only' ? ipInputs().map((input) => input.value.trim()).filter(Boolean) : [];
}

/** Ticked ids; an empty list means no restriction, so every box starts ticked. */
const isTicked = (selectedIds, id) => selectedIds.length === 0 || selectedIds.includes(id);

/** Label of each "select all" button follows whether every box of its group is ticked. */
function refreshToggleAll() {
    form.querySelectorAll('[data-toggle-all]').forEach((button) => {
        const boxes = [...document.getElementById(button.dataset.toggleAll).querySelectorAll('input[type="checkbox"]')];

        button.classList.toggle('hidden', boxes.length === 0);
        button.textContent = boxes.every((box) => box.checked) ? button.dataset.deselectLabel : button.dataset.selectLabel;
    });
}

function wireToggleAll() {
    form.querySelectorAll('[data-toggle-all]').forEach((button) => {
        const container = document.getElementById(button.dataset.toggleAll);

        button.addEventListener('click', () => {
            const boxes = [...container.querySelectorAll('input[type="checkbox"]')];
            const tick = !boxes.every((box) => box.checked);

            boxes.forEach((box) => {
                box.checked = tick;
            });
            refreshToggleAll();
        });
        container.addEventListener('change', refreshToggleAll);
    });
}

/** The checked values of a group, or [] (no restriction) when every box is ticked. */
function checkedValues(selector, cast = String) {
    const boxes = [...form.querySelectorAll(selector)];
    const checked = boxes.filter((box) => box.checked);

    return checked.length === boxes.length ? [] : checked.map((box) => cast(box.value));
}

/** One checkbox per account; accounts in `selectedIds` are ticked (all when it is empty). */
async function renderAccounts(selectedIds) {
    const container = form.querySelector('[data-allowed-accounts]');
    const loaded = await getAll(routes.accounts.index);
    const accounts = loaded ?? [];

    if (accounts.length === 0) {
        const message = document.createElement('p');

        message.className = 'font-body text-body-sm text-on-surface-variant';
        message.textContent = loaded === null ? container.dataset.errorLabel : container.dataset.emptyLabel;
        container.replaceChildren(message);
        refreshToggleAll();

        return;
    }

    container.replaceChildren(
        ...accounts.map((account) => {
            const label = document.createElement('label');
            const checkbox = document.createElement('input');

            label.className = 'flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface';
            checkbox.type = 'checkbox';
            checkbox.value = account.id;
            checkbox.checked = isTicked(selectedIds, account.id);
            checkbox.setAttribute('data-allowed-account', '');
            checkbox.className =
                'h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
            label.append(checkbox, document.createTextNode(account.name));

            return label;
        }),
    );
    refreshToggleAll();
}

async function loadItem() {
    const { ok, data } = await get(routes.webhooks.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('token').value = data.data.token ?? '';
        const ips = data.data.allowed_ips ?? [];

        ips.forEach((ip) => addIpRow(ip));
        setIpMode(ips.length > 0 ? 'only' : 'all');
        form.elements.namedItem('url').value = data.data.url ?? '';
        form.elements.namedItem('is_active').checked = Boolean(data.data.is_active);
        form.elements.namedItem('can_create_accounts').checked = Boolean(data.data.can_create_accounts);
        form.querySelectorAll('[name="allowed_types[]"]').forEach((box) => {
            box.checked = isTicked(data.data.allowed_types ?? [], box.value);
        });
        refreshToggleAll();
        await renderAccounts(data.data.allowed_account_ids ?? []);
    }

    hideSkeleton('webhook-form', 'webhook-form-skeleton');
}

if (form) {
    if (itemId) {
        loadItem();
    } else {
        renderAccounts([]);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearFieldErrors(form);
        hideSnackbar();

        const clientErrors = validateForm(form);
        if (Object.keys(clientErrors).length > 0) {
            showFieldErrors(form, clientErrors);
            return;
        }

        const groups = {
            allowed_account_ids: '[data-allowed-account]',
            allowed_types: '[name="allowed_types[]"]',
        };
        const groupErrors = {};

        if (form.querySelector('[name="ip_mode"]:checked').value === 'only' && enteredIps().length === 0) {
            groupErrors.allowed_ips = [form.querySelector('#allowed_ips').dataset.ipRequiredMessage];
        }

        Object.entries(groups).forEach(([field, selector]) => {
            const boxes = [...form.querySelectorAll(selector)];

            if (boxes.length > 0 && boxes.every((box) => !box.checked)) {
                groupErrors[field] = [form.dataset.pickOneMessage];
            }
        });

        if (Object.keys(groupErrors).length > 0) {
            showFieldErrors(form, groupErrors);
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        const payload = {
            name: form.elements.namedItem('name').value,
            allowed_ips: enteredIps().join('\n'),
            allowed_account_ids: checkedValues('[data-allowed-account]', Number),
            allowed_types: checkedValues('[name="allowed_types[]"]'),
            can_create_accounts: form.elements.namedItem('can_create_accounts').checked,
            is_active: form.elements.namedItem('is_active').checked,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.webhooks.update(itemId), payload) : post(routes.webhooks.store, payload));

            if (ok) {
                sessionStorage.setItem('webhooks-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.webhooks;
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'name');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'name');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
