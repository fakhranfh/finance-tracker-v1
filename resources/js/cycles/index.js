import { hideSkeleton, setButtonLoading, showSkeleton } from '../shared/loading-state.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { hideSnackbar } from '../shared/error-snackbar.js';
import { closeModal, openModal } from '../shared/modal.js';
import { formatDate } from '../shared/datetime.js';
import { get, post, put, del } from '../shared/http.js';
import { createGenerateButton } from '../shared/table/table-generate.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';
import { setupEntryModals } from './entry-modals.js';
import { showError, showFlash, showPendingFlash } from './flash.js';
import { createPager } from './pager.js';
import { entryInfo } from './stats.js';
import { createText, statusClasses } from './ui.js';

const page = document.getElementById('cycles-page');
const level = page.dataset.level;
const labels = JSON.parse(page.dataset.labels);
const icons = JSON.parse(page.dataset.icons);
const { t, daysText } = createText(labels);

const container = document.getElementById('cycles-grid') ?? document.getElementById('cycles-timeline');
const emptyState = document.getElementById('cycles-empty');
const search = document.getElementById('cycles-search');
const tileTemplate = document.getElementById('cycle-tile-template');
const addTileTemplate = document.getElementById('cycle-add-tile-template');
const entryTemplate = document.getElementById('cycle-entry-template');

/** Ids of the purchases ticked for a bulk delete; they stay ticked while paging. */
const selected = new Set();
let pageIds = [];

const state = { items: [], meta: null, monthlyTotal: 0, category: null, type: null };

let loadToken = 0;
let searchTimer;

const pager = createPager({
    pagination: document.querySelectorAll('[data-pagination]'),
    selects: document.querySelectorAll('[data-per-page-select]'),
    onChange: () => reloadPage(),
});

/** The endpoint this level lists from; every level is paged and searched by the API. */
function listUrl() {
    if (level === 'groups') {
        return routes.cycles.categories;
    }

    return level === 'types' ? routes.cycles.categoryTypes(page.dataset.group) : routes.cycles.typeEntries(page.dataset.type);
}

/** The dev-only endpoint that creates sample data for this level. */
function generateUrl() {
    if (level === 'groups') {
        return routes.cycles.generateCategories;
    }

    return level === 'types' ? routes.cycles.generateTypes(page.dataset.group) : routes.cycles.generateEntries(page.dataset.type);
}

/** Fetches the current page for the current search; steps back when that page no longer exists. */
async function load() {
    const params = new URLSearchParams({ page: pager.page, per_page: pager.perPage });
    const query = search.value.trim();

    if (query) {
        params.set('search', query);
    }

    let response = await get(`${listUrl()}?${params}`);

    if (response.ok && response.data.data.length === 0 && response.data.meta.current_page > response.data.meta.last_page) {
        params.set('page', response.data.meta.last_page);
        response = await get(`${listUrl()}?${params}`);
    }

    if (!response.ok) {
        return false;
    }

    const { data } = response;

    state.items = data.data;
    state.meta = data.meta;

    if (level === 'groups') {
        state.monthlyTotal = data.summary.monthly_total;
    } else if (level === 'types') {
        state.category = data.category;
    } else if (String(data.type.category_id) === page.dataset.group) {
        state.type = data.type;
    } else {
        return false;
    }

    return true;
}

/** What the page shows for its level, from the data `load()` fetched. */
function scope() {
    if (level === 'groups') {
        return { title: null, back: null, monthly: state.monthlyTotal };
    }

    if (level === 'types') {
        return { title: state.category.name, back: routes.pages.cycles, monthly: state.category.monthly };
    }

    return { title: state.type.name, back: routes.pages.cycleGroup(state.type.category_id), monthly: state.type.monthly ?? 0 };
}

const fieldOf = (node, name) => node.querySelector(`[data-field="${name}"]`);

function tileNode(item) {
    const card = tileTemplate.content.firstElementChild.cloneNode(true);
    const tile = card.querySelector('[data-tile]');
    const icon = fieldOf(card, 'icon');
    const select = card.querySelector('[data-select]');

    card.dataset.id = item.id;
    select.checked = selected.has(item.id);
    select.setAttribute('aria-label', item.name);
    card.querySelector('[data-edit]').setAttribute('aria-label', `${card.querySelector('[data-edit]').textContent.trim()} ${item.name}`);
    card.querySelector('[data-delete]').setAttribute('aria-label', `${card.querySelector('[data-delete]').textContent.trim()} ${item.name}`);

    tile.href = level === 'groups' ? routes.pages.cycleGroup(item.id) : routes.pages.cycleType(state.category.id, item.id);
    icon.innerHTML = icons[item.icon] ?? icons[page.dataset.defaultIcon];

    if (item.color) {
        icon.style.color = item.color;
    }

    fieldOf(card, 'name').textContent = item.name;
    fieldOf(card, 'meta').textContent = item.monthly ? t('monthly_short', { amount: formatMoney(item.monthly) }) : '';

    return card;
}

function entryNode(entry) {
    const node = entryTemplate.content.firstElementChild.cloneNode(true);
    const info = entryInfo(entry, state.type.avg_days);
    const status = fieldOf(node, 'status');

    node.querySelector('[data-entry]').href = routes.pages.cycleEntry(state.type.category_id, state.type.id, entry.id);
    fieldOf(node, 'name').textContent = entry.name;
    fieldOf(node, 'date').textContent = t('bought_on', { date: formatDate(entry.bought_at) });
    fieldOf(node, 'meta').textContent = `${formatMoney(entry.price)} · ${info.done ? daysText(info.days) : t('usage_running', { days: daysText(info.days) })}`;
    status.textContent = labels[`status_${info.status}`];
    status.classList.add(...statusClasses[info.status]);
    node.dataset.id = entry.id;

    const select = node.querySelector('[data-select]');

    select.checked = selected.has(entry.id);
    select.setAttribute('aria-label', entry.name);

    // Only a purchase that is still in use can be marked as finished.
    const finish = node.querySelector('[data-finish]');

    finish.classList.toggle('hidden', info.done);
    finish.classList.toggle('inline-flex', !info.done);

    return node;
}

function render() {
    const current = scope();
    const query = search.value.trim();
    const isLast = state.meta.current_page >= state.meta.last_page;
    const nodes = state.items.map((item) => (level === 'entries' ? entryNode(item) : tileNode(item)));

    pager.render(state.meta);

    if (level !== 'entries' && !query && isLast) {
        const addTile = addTileTemplate.content.firstElementChild.cloneNode(true);

        fieldOf(addTile, 'label').textContent = document.getElementById('cycle-tile-form').dataset.addTitle;
        nodes.push(addTile);
    }

    pageIds = state.items.map((item) => item.id);
    container.replaceChildren(...nodes);
    syncSelection();
    emptyState.textContent = labels[query ? 'no_results' : `empty_${level}`];
    emptyState.classList.toggle('hidden', state.items.length > 0);
    document.querySelector('[data-summary="monthly"]').textContent = formatMoney(current.monthly);
}

/** Ids of every item matching the current search across all pages, fetched at the API's largest page size. */
async function fetchAllIds() {
    const ids = [];
    const query = search.value.trim();
    let pageNumber = 1;
    let lastPage = 1;

    do {
        const params = new URLSearchParams({ page: pageNumber, per_page: 100 });

        if (query) {
            params.set('search', query);
        }

        const response = await get(`${listUrl()}?${params}`);

        if (!response.ok) {
            throw new Error('select all failed');
        }

        response.data.data.forEach((item) => ids.push(item.id));
        lastPage = response.data.meta.last_page;
        pageNumber += 1;
    } while (pageNumber <= lastPage);

    return ids;
}

/** Shows how many items are ticked,the bulk delete button and the state of the select-all box. */
function syncSelection() {
    const count = selected.size;
    const countLabel = document.getElementById('cycles-selected-count');
    const selectAll = document.getElementById('cycles-select-all');
    const onPage = pageIds.filter((id) => selected.has(id)).length;

    countLabel.textContent = countLabel.dataset.template.replace(':n', count);
    countLabel.classList.toggle('hidden', count === 0);
    document.getElementById('cycles-bulk-delete').classList.toggle('hidden', count === 0);
    selectAll.checked = pageIds.length > 0 && onPage === pageIds.length;
    selectAll.indeterminate = onPage > 0 && onPage < pageIds.length;
    selectAll.disabled = pageIds.length === 0;

    const selectMatching = document.getElementById('cycles-select-all-matching');
    const total = state.meta?.total ?? 0;

    selectMatching.querySelector('[data-button-label]').textContent = selectMatching.dataset.template.replace(':total', total);
    selectMatching.classList.toggle('!hidden', total <= pageIds.length || count >= total);
}

/** The item (category, item name or purchase) a card or row belongs to. */
const itemOf = (node) => state.items.find((item) => item.id === Number(node.closest('[data-id]').dataset.id));

/** Ticking items, select-all on the page and the bulk delete button; `openBulkDelete` asks for the confirmation. */
function setupSelection(openBulkDelete) {
    container.addEventListener('change', (event) => {
        const box = event.target.closest('[data-select]');

        if (box) {
            const id = itemOf(box).id;

            if (box.checked) {
                selected.add(id);
            } else {
                selected.delete(id);
            }

            syncSelection();
        }
    });

    document.getElementById('cycles-select-all').addEventListener('change', (event) => {
        pageIds.forEach((id) => (event.target.checked ? selected.add(id) : selected.delete(id)));
        container.querySelectorAll('[data-select]').forEach((box) => (box.checked = event.target.checked));
        syncSelection();
    });

    // Ticks every item matching the search on all pages, not only the ones shown.
    const selectMatching = document.getElementById('cycles-select-all-matching');

    selectMatching.addEventListener('click', async () => {
        setButtonLoading(selectMatching, true);

        try {
            (await fetchAllIds()).forEach((id) => selected.add(id));
            container.querySelectorAll('[data-select]').forEach((box) => (box.checked = true));
        } catch {
            showError(labels.error_generic);
        } finally {
            setButtonLoading(selectMatching, false);
            syncSelection();
        }
    });

    document.getElementById('cycles-bulk-delete').addEventListener('click', openBulkDelete);
}

async function refresh(message) {
    await load();
    render();
    showFlash(message);
}

function setupTileModal() {
    const form = document.getElementById('cycle-tile-form');
    const deleteButton = document.getElementById('confirm-delete-tile');
    const bulkButton = document.getElementById('confirm-bulk-delete-tiles');
    let editing = null;
    let target = null;

    const open = (item = null) => {
        editing = item;
        clearFieldErrors(form);
        form.reset();

        if (item) {
            form.elements.namedItem('tile_name').value = item.name;
            form.elements.namedItem('icon').value = item.icon;
        }

        document.getElementById('cycle-tile-modal-title').textContent = item ? form.dataset.editTitle : form.dataset.addTitle;
        openModal('cycle-tile-modal');
    };

    container.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-edit]');
        const remove = event.target.closest('[data-delete]');

        if (event.target.closest('[data-add-tile]')) {
            open();
        } else if (edit) {
            open(itemOf(edit));
        } else if (remove) {
            target = itemOf(remove);
            openModal('cycle-tile-delete-modal');
        }
    });
    document.getElementById('cycles-add-category')?.addEventListener('click', () => open());
    setupSelection(() => openModal('cycle-tile-bulk-delete-modal'));

    /** Runs a delete request behind the confirm button's loading state, then reloads the list. */
    async function destroy(button, modalId, request, message) {
        setButtonLoading(button, true);

        try {
            const { ok, data } = await request();

            if (ok) {
                await refresh(message);
            } else {
                showError(data.message ?? labels.error_generic);
            }
        } catch {
            showError(labels.error_generic);
        } finally {
            setButtonLoading(button, false);
            closeModal(modalId);
        }
    }

    // A category is a normal expense category, so deleting it here deletes it everywhere; an item name only here.
    deleteButton.addEventListener('click', () =>
        destroy(
            deleteButton,
            'cycle-tile-delete-modal',
            async () => {
                const response = await del(level === 'groups' ? routes.categories.destroy(target.id) : routes.cycles.type(target.id));

                if (response.ok) {
                    selected.delete(target.id);
                }

                return response;
            },
            level === 'groups' ? labels.flash_group_deleted : labels.flash_type_deleted,
        ),
    );

    bulkButton.addEventListener('click', () =>
        destroy(
            bulkButton,
            'cycle-tile-bulk-delete-modal',
            async () => {
                const ids = [...selected];
                const response = await del(level === 'groups' ? routes.categories.bulkDestroy : routes.cycles.categoryTypes(state.category.id), { ids });

                if (response.ok) {
                    selected.clear();
                }

                return response;
            },
            level === 'groups' ? labels.flash_groups_deleted : labels.flash_types_deleted,
        ),
    );

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFieldErrors(form);
        hideSnackbar();

        const clientErrors = validateForm(form);
        const name = form.elements.namedItem('tile_name').value.trim();
        const icon = form.elements.namedItem('icon').value;

        if (Object.keys(clientErrors).length) {
            showFieldErrors(form, clientErrors);

            return;
        }

        if (!name) {
            showGenericError(form, labels.error_invalid_name, 'tile_name');

            return;
        }

        const submit = form.querySelector('button[type="submit"]');

        setButtonLoading(submit, true);

        try {
            const { ok, status, data } = await saveRequest(editing, name, icon);

            if (ok) {
                closeModal('cycle-tile-modal');
                await refresh(saveMessage(editing));
            } else if (status === 422 && data.errors) {
                showFieldErrors(form, { tile_name: data.errors.name ?? Object.values(data.errors)[0] });
            } else {
                showGenericError(form, data.message ?? labels.error_generic, 'tile_name');
            }
        } catch {
            showGenericError(form, labels.error_generic, 'tile_name');
        } finally {
            setButtonLoading(submit, false);
        }
    });
}

/** Creates or updates a category (a normal expense category, also shown in Manage Categories) or an item name. */
function saveRequest(editing, name, icon) {
    if (level === 'groups') {
        return editing
            ? put(routes.categories.update(editing.id), { name, type: 'expense', color: editing.color, icon })
            : post(routes.categories.store, { name, type: 'expense', color: '#2563EB', icon });
    }

    return editing ? put(routes.cycles.type(editing.id), { name, icon }) : post(routes.cycles.categoryTypes(state.category.id), { name, icon });
}

function saveMessage(editing) {
    if (level === 'groups') {
        return editing ? labels.flash_group_updated : labels.flash_group;
    }

    return editing ? labels.flash_type_updated : labels.flash_type;
}

function setupEntries() {
    const modals = setupEntryModals({
        labels,
        async onSave(values, editing) {
            const response = editing ? await put(routes.cycles.entry(editing.id), values) : await post(routes.cycles.typeEntries(state.type.id), values);

            if (response.ok) {
                await refresh(editing ? labels.flash_updated : labels.flash_added);
            }

            return response;
        },
        async onFinish(entry, finishedAt) {
            const response = await put(routes.cycles.finishEntry(entry.id), { finished_at: finishedAt });

            if (response.ok) {
                await refresh(labels.flash_finished);
            }

            return response;
        },
        async onDelete(entry) {
            const response = await del(routes.cycles.entry(entry.id));

            if (response.ok) {
                selected.delete(entry.id);
                await refresh(labels.flash_deleted);
            }

            return response;
        },
        async onBulkDelete(ids) {
            const response = await del(routes.cycles.typeEntries(state.type.id), { ids });

            if (response.ok) {
                selected.clear();
                await refresh(labels.flash_bulk_deleted);
            }

            return response;
        },
    });

    document.getElementById('cycle-fab-add').addEventListener('click', () => modals.openAdd(state.type.last_entry));
    container.addEventListener('click', (event) => {
        const actions = { '[data-finish]': modals.openFinish, '[data-edit]': modals.openEdit, '[data-delete]': modals.openDelete };

        Object.entries(actions).forEach(([selector, open]) => {
            const button = event.target.closest(selector);

            if (button) {
                open(itemOf(button));
            }
        });
    });

    setupSelection(() => modals.openBulkDelete([...selected]));
}

/** Loads the page the pager and search ask for, behind the skeleton, and ignores an answer that a newer request replaced. */
async function reloadPage() {
    const token = (loadToken += 1);

    showSkeleton('cycles-content', 'cycles-skeleton');

    const ok = await load();

    if (token !== loadToken) {
        return;
    }

    if (ok) {
        render();
    } else {
        showError(labels.error_generic);
    }

    hideSkeleton('cycles-content', 'cycles-skeleton');
}

search.addEventListener('input', () => {
    pager.reset();
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reloadPage, 300);
});

const found = await load();

page.querySelector('[data-title-skeleton]')?.classList.add('hidden');

if (found) {
    const current = scope();

    if (current.title) {
        page.querySelector('[data-title]').textContent = current.title;
    }

    if (current.back) {
        const back = document.getElementById('cycles-back');

        back.href = current.back;
        back.classList.remove('hidden');
        back.classList.add('inline-flex');
    }

    if (level === 'entries') {
        setupEntries();
    } else {
        setupTileModal();
    }

    // Dev only: the button is rendered when the app runs locally, so with none in the page this binds nothing.
    createGenerateButton({
        button: document.getElementById('generate-cycles'),
        countInput: document.getElementById('generate-count'),
        url: generateUrl(),
        onGenerated: refresh,
    });

    render();
    hideSkeleton('cycles-content', 'cycles-skeleton');
    showPendingFlash();
} else {
    document.getElementById('cycles-skeleton').classList.add('hidden');
    document.getElementById('cycles-missing').classList.remove('hidden');
}
