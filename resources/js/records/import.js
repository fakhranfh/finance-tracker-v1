import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { formatDateTime } from '../shared/datetime.js';
import { closeModal, openModal } from '../shared/modal.js';
import { del, get, post, put, upload } from '../shared/http.js';
import { searchableSelect } from '../shared/searchable-select.js';
import { routes } from '../routes.js';

const root = document.getElementById('record-import');
const labels = JSON.parse(root.dataset.labels);
const fill = (template, values) => Object.entries(values).reduce((text, [key, value]) => text.replace(`:${key}`, value), template);

const STEPS = ['upload', 'columns', 'values', 'result'];
const sections = Object.fromEntries(STEPS.map((step) => [step, document.getElementById(`import-step-${step}`)]));
const skeleton = document.getElementById('import-skeleton');
const forms = {
    upload: document.getElementById('import-upload-form'),
    columns: document.getElementById('import-columns-form'),
    values: document.getElementById('import-values-form'),
};
const fieldSelects = [...forms.columns.querySelectorAll('[data-import-field]')];
const fieldPickers = fieldSelects.map((select) => searchableSelect(select));

/**
 * The import being worked on. Its progress (step, column choices, value choices) lives on the server,
 * so it survives a reload, a closed tab and another device, and several imports can be open at once.
 */
const state = { id: null, step: null, columns: [], sample: [], mapping: {} };

function setUrl(id) {
    history.replaceState(null, '', id ? `${routes.pages.recordImport}?import=${id}` : routes.pages.recordImport);
}

function saveProgress(patch) {
    if (state.id === null) {
        return;
    }

    put(routes.records.importShow(state.id), patch).catch(() => {});
}

/** Show one step (or the loading skeleton when `step` is null) and say where the user is. */
function show(step) {
    Object.values(sections).forEach((section) => section.classList.add('hidden'));
    skeleton.classList.toggle('hidden', step !== null);

    if (step === null) {
        return;
    }

    if ((step === 'columns' || step === 'values') && state.step !== step) {
        state.step = step;
        saveProgress({ step });
    }

    sections[step].classList.remove('hidden');
    document.getElementById('import-step-label').textContent = fill(labels.stepLabel, {
        step: STEPS.indexOf(step) + 1,
        total: STEPS.length,
        name: labels.steps[STEPS.indexOf(step)],
    });
}

function beginSubmit(form) {
    clearFieldErrors(form);
    hideSnackbar();

    const clientErrors = validateForm(form);

    if (Object.keys(clientErrors).length > 0) {
        showFieldErrors(form, clientErrors);

        return null;
    }

    const button = form.querySelector('button[type="submit"]');
    setButtonLoading(button, true);

    return button;
}

function showFailure(form, status, data, field) {
    if (status === 422 && data.errors) {
        showFieldErrors(form, data.errors);
    } else {
        showGenericError(form, data.message ?? labels.failed, field);
    }
}

function exampleOf(columnIndex) {
    const row = state.sample.find((cells) => cells[columnIndex] !== null && cells[columnIndex] !== undefined);

    return row ? String(row[columnIndex]) : '';
}

function showExample(select) {
    const target = forms.columns.querySelector(`[data-import-example="${select.dataset.importField}"]`);
    const example = select.value === '' ? '' : exampleOf(Number(select.value));

    target.textContent = example === '' ? '' : fill(labels.example, { value: example });
}

function currentMapping() {
    return Object.fromEntries(fieldSelects.map((select) => [select.dataset.importField, select.value === '' ? null : Number(select.value)]));
}

function renderColumns(item) {
    state.columns = item.columns;
    state.sample = item.sample;

    fieldSelects.forEach((select) => {
        select.replaceChildren(
            select.options[0],
            ...item.columns.map((column) => new Option(column.name, column.index)),
        );
        select.value = item.mapping[select.dataset.importField] ?? '';
        showExample(select);
    });
    fieldPickers.forEach((picker) => picker.refresh());
}

fieldSelects.forEach((select) => select.addEventListener('change', () => {
    showExample(select);
    saveProgress({ mapping: currentMapping() });
}));

let valueRowCount = 0;

/** A row with the file's name on the left and its select on the right; the select becomes searchable once the row is on the page. */
function valueRow(value, select) {
    const row = document.createElement('div');
    const name = document.createElement('label');

    select.id = `import-value-${valueRowCount += 1}`;
    name.htmlFor = select.id;

    row.className = 'grid grid-cols-1 items-center gap-2 sm:grid-cols-2 sm:gap-4';
    name.className = 'break-words font-body text-body-md font-medium text-on-surface';
    name.textContent = value;
    row.append(name, select);

    return row;
}

function optionGroup(label, options) {
    const group = document.createElement('optgroup');
    group.label = label;
    group.append(...options);

    return group;
}

const accountList = document.getElementById('import-accounts-list');
const categoryList = document.getElementById('import-categories-list');
const choiceKey = (list, select) => `${list.dataset.importList}|${select.dataset.value}`;

function saveChoices() {
    saveProgress({
        choices: Object.fromEntries(
            [accountList, categoryList].flatMap((list) => [...list.querySelectorAll('select')].map((select) => [choiceKey(list, select), select.value])),
        ),
    });
}

[accountList, categoryList].forEach((list) => list.addEventListener('change', saveChoices));

function renderValues(values, choices = {}) {
    const restore = (list, select) => {
        const saved = choices[choiceKey(list, select)];

        if (saved !== undefined && [...select.options].some((option) => option.value === saved)) {
            select.value = saved;
        }
    };

    accountList.replaceChildren(...values.accounts.map((entry) => {
        const select = document.createElement('select');

        select.dataset.value = entry.value;
        select.append(
            new Option(fill(labels.createAccount, { name: entry.value }), 'create'),
            optionGroup(labels.existingAccounts, values.existing_accounts.map((account) => new Option(account.name, `existing:${account.id}`))),
            new Option(labels.unmappedAccount, 'unmapped'),
        );
        select.value = entry.suggestion.action === 'existing' ? `existing:${entry.suggestion.id}` : 'create';
        restore(accountList, select);

        return valueRow(entry.value, select);
    }));
    document.getElementById('import-accounts-empty').classList.toggle('hidden', values.accounts.length > 0);

    const categoryOptions = (type) => values.existing_categories
        .filter((category) => category.type === type)
        .map((category) => new Option(category.name, category.id));

    categoryList.replaceChildren(...values.categories.map((entry) => {
        const select = document.createElement('select');

        select.dataset.value = entry.value;
        select.append(
            new Option(fill(labels.createCategory, { name: entry.value }), 'create'),
            new Option(labels.uncategorized, 'none'),
            optionGroup(labels.incomeCategories, categoryOptions('income')),
            optionGroup(labels.expenseCategories, categoryOptions('expense')),
        );
        select.value = entry.suggestion.action === 'existing' ? String(entry.suggestion.id) : 'create';
        restore(categoryList, select);

        return valueRow(entry.value, select);
    }));
    document.getElementById('import-categories-empty').classList.toggle('hidden', values.categories.length > 0);

    [accountList, categoryList].forEach((list) => list.querySelectorAll('select').forEach((select) => searchableSelect(select)));
}

function renderResult(result) {
    const accounts = document.getElementById('import-result-accounts');
    const categories = document.getElementById('import-result-categories');
    const skipped = document.getElementById('import-result-skipped');

    document.getElementById('import-result-imported').textContent = fill(labels.imported, { count: result.imported });

    accounts.textContent = fill(labels.createdAccounts, { names: result.created_accounts.join(', ') });
    accounts.classList.toggle('hidden', result.created_accounts.length === 0);

    categories.textContent = fill(labels.createdCategories, { names: result.created_categories.join(', ') });
    categories.classList.toggle('hidden', result.created_categories.length === 0);

    document.getElementById('import-result-skipped-title').textContent = fill(labels.skipped, { count: result.skipped });
    document.getElementById('import-result-errors').replaceChildren(...result.errors.map((error) => {
        const item = document.createElement('li');

        item.textContent = fill(labels.errorRow, { row: error.row, message: error.message });

        return item;
    }));
    skipped.classList.toggle('hidden', result.skipped === 0);
}

/** Open an import where its saved progress left off. */
function resume(item) {
    state.id = item.id;
    state.step = item.step;
    setUrl(item.id);
    renderColumns(item);

    if (item.step === 'values' && item.values) {
        state.mapping = item.mapping;
        renderValues(item.values, item.choices);
        show('values');
    } else {
        show('columns');
    }
}

/** Back to the upload step; the import stays open in the "in progress" list. */
function leave() {
    state.id = null;
    state.step = null;
    setUrl(null);
    forms.upload.reset();
    clearFieldErrors(forms.upload);
    show('upload');
    loadProgressList();
}

const progressPanel = document.getElementById('import-progress');
const progressList = document.getElementById('import-progress-list');
let discarding = null;

function progressRow(item) {
    const row = document.createElement('li');
    const info = document.createElement('div');
    const name = document.createElement('p');
    const detail = document.createElement('p');
    const actions = document.createElement('div');
    const resumeButton = document.createElement('button');
    const discardButton = document.createElement('button');

    row.className = 'flex flex-wrap items-center justify-between gap-3 py-3';
    row.dataset.importId = item.id;
    name.className = 'break-all font-body text-body-md font-medium text-on-surface';
    name.textContent = item.filename;
    detail.className = 'font-body text-body-sm text-on-surface-variant';
    detail.textContent = [labels.steps[item.step === 'values' ? 2 : 1], fill(labels.rowCount, { count: item.row_count }), formatDateTime(item.updated_at)].join(' · ');
    info.append(name, detail);

    actions.className = 'flex items-center gap-4';
    resumeButton.type = 'button';
    resumeButton.className = 'cursor-pointer font-body text-body-md font-medium text-primary hover:underline';
    resumeButton.textContent = labels.resume;
    resumeButton.setAttribute('aria-label', `${labels.resume}: ${item.filename}`);
    resumeButton.addEventListener('click', () => openImport(item.id));
    discardButton.type = 'button';
    discardButton.className = 'cursor-pointer font-body text-body-md text-error hover:underline';
    discardButton.textContent = labels.discard;
    discardButton.setAttribute('aria-label', `${labels.discard}: ${item.filename}`);
    discardButton.addEventListener('click', () => {
        discarding = item.id;
        openModal('discard-import-modal');
    });
    actions.append(resumeButton, discardButton);
    row.append(info, actions);

    return row;
}

async function loadProgressList() {
    const { ok, data } = await get(routes.records.imports);

    progressList.replaceChildren(...(ok ? data.data : []).map(progressRow));
    progressPanel.classList.toggle('hidden', !ok || data.data.length === 0);
}

async function openImport(id) {
    show(null);

    const { ok, data } = await get(routes.records.importShow(id));

    if (ok) {
        resume(data.data);
    } else {
        leave();
    }
}

document.getElementById('confirm-discard-import').addEventListener('click', async (event) => {
    const button = event.currentTarget;

    setButtonLoading(button, true);

    try {
        await del(routes.records.importShow(discarding));
        closeModal('discard-import-modal');
        await loadProgressList();
    } finally {
        setButtonLoading(button, false);
    }
});

forms.upload.addEventListener('submit', async (event) => {
    event.preventDefault();

    const button = beginSubmit(forms.upload);

    if (!button) {
        return;
    }

    try {
        const { ok, status, data } = await upload(routes.records.imports, new FormData(forms.upload));

        if (ok) {
            resume(data.data);
        } else {
            showFailure(forms.upload, status, data, 'file');
        }
    } catch {
        showGenericError(forms.upload, labels.failed, 'file');
    } finally {
        setButtonLoading(button, false);
    }
});

forms.columns.addEventListener('submit', async (event) => {
    event.preventDefault();

    const button = beginSubmit(forms.columns);

    if (!button) {
        return;
    }

    state.mapping = currentMapping();
    show(null);

    try {
        const { ok, status, data } = await post(routes.records.importValues(state.id), { mapping: state.mapping });

        if (ok) {
            state.step = 'values';
            renderValues(data.data.values);
            show('values');

            return;
        }

        show('columns');
        showFailure(forms.columns, status, data, 'import');
    } catch {
        show('columns');
        showGenericError(forms.columns, labels.failed, 'import');
    } finally {
        setButtonLoading(button, false);
    }
});

/** Accounts use "existing:ID" values; categories a bare ID. Anything else is the action itself. */
function choiceOf(select) {
    const value = select.dataset.value;
    const id = select.value.startsWith('existing:') ? select.value.slice('existing:'.length) : select.value;

    if (/^\d+$/.test(id)) {
        return { value, action: 'existing', id: Number(id) };
    }

    return { value, action: select.value };
}

forms.values.addEventListener('submit', async (event) => {
    event.preventDefault();

    const button = beginSubmit(forms.values);

    if (!button) {
        return;
    }

    try {
        const { ok, status, data } = await post(routes.records.importRun(state.id), {
            accounts: [...accountList.querySelectorAll('select')].map(choiceOf),
            categories: [...categoryList.querySelectorAll('select')].map(choiceOf),
        });

        if (ok) {
            state.id = null;
            state.step = null;
            setUrl(null);
            renderResult(data.data);
            show('result');
        } else {
            showFailure(forms.values, status, data, 'import');
        }
    } catch {
        showGenericError(forms.values, labels.failed, 'import');
    } finally {
        setButtonLoading(button, false);
    }
});

document.querySelectorAll('[data-import-back]').forEach((button) => {
    button.addEventListener('click', () => {
        if (button.dataset.importBack === 'upload') {
            leave();
        } else {
            show(button.dataset.importBack);
        }
    });
});

const requestedId = Number(new URLSearchParams(window.location.search).get('import'));

if (requestedId > 0) {
    await openImport(requestedId);
} else {
    show('upload');
}

loadProgressList();
