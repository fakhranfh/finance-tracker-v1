import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { post, upload } from '../shared/http.js';
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

const state = { token: null, columns: [], sample: [], mapping: {} };

/** Show one step (or the loading skeleton when `step` is null) and say where the user is. */
function show(step) {
    Object.values(sections).forEach((section) => section.classList.add('hidden'));
    skeleton.classList.toggle('hidden', step !== null);

    if (step !== null) {
        sections[step].classList.remove('hidden');
        document.getElementById('import-step-label').textContent = fill(labels.stepLabel, {
            step: STEPS.indexOf(step) + 1,
            total: STEPS.length,
            name: labels.steps[STEPS.indexOf(step)],
        });
    }
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

function renderColumns(preview) {
    state.token = preview.token;
    state.columns = preview.columns;
    state.sample = preview.sample;

    fieldSelects.forEach((select) => {
        select.replaceChildren(
            select.options[0],
            ...preview.columns.map((column) => new Option(column.name, column.index)),
        );
        select.value = preview.suggested_mapping[select.dataset.importField] ?? '';
        showExample(select);
    });
    fieldPickers.forEach((picker) => picker.refresh());
}

fieldSelects.forEach((select) => select.addEventListener('change', () => showExample(select)));

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

function renderValues(values) {
    const accountList = document.getElementById('import-accounts-list');
    const categoryList = document.getElementById('import-categories-list');

    const pickers = [];

    accountList.replaceChildren(...values.accounts.map((entry) => {
        const select = document.createElement('select');

        select.dataset.value = entry.value;
        select.append(
            new Option(fill(labels.createAccount, { name: entry.value }), 'create'),
            optionGroup(labels.existingAccounts, values.existing_accounts.map((account) => new Option(account.name, `existing:${account.id}`))),
            new Option(labels.unmappedAccount, 'unmapped'),
        );
        select.value = entry.suggestion.action === 'existing' ? `existing:${entry.suggestion.id}` : 'create';

        return valueRow(entry.value, select);
    }));
    pickers.push(...accountList.querySelectorAll('select'));
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

        return valueRow(entry.value, select);
    }));
    pickers.push(...categoryList.querySelectorAll('select'));
    pickers.forEach((select) => searchableSelect(select));
    document.getElementById('import-categories-empty').classList.toggle('hidden', values.categories.length > 0);
}

function renderResult(result) {
    const accounts = document.getElementById('import-result-accounts');
    const skipped = document.getElementById('import-result-skipped');

    document.getElementById('import-result-imported').textContent = fill(labels.imported, { count: result.imported });

    accounts.textContent = fill(labels.createdAccounts, { names: result.created_accounts.join(', ') });
    accounts.classList.toggle('hidden', result.created_accounts.length === 0);

    const categories = document.getElementById('import-result-categories');

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

forms.upload.addEventListener('submit', async (event) => {
    event.preventDefault();

    const button = beginSubmit(forms.upload);

    if (!button) {
        return;
    }

    try {
        const { ok, status, data } = await upload(routes.records.importPreview, new FormData(forms.upload));

        if (ok) {
            renderColumns(data.data);
            show('columns');
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

    state.mapping = Object.fromEntries(
        fieldSelects.filter((select) => select.value !== '').map((select) => [select.dataset.importField, Number(select.value)]),
    );
    show(null);

    try {
        const { ok, status, data } = await post(routes.records.importValues, { token: state.token, mapping: state.mapping });

        if (ok) {
            renderValues(data.data);
            show('values');

            return;
        }

        show('columns');
        showFailure(forms.columns, status, data, 'token');
    } catch {
        show('columns');
        showGenericError(forms.columns, labels.failed, 'token');
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
        const { ok, status, data } = await post(routes.records.import, {
            token: state.token,
            mapping: state.mapping,
            accounts: [...document.querySelectorAll('#import-accounts-list select')].map(choiceOf),
            categories: [...document.querySelectorAll('#import-categories-list select')].map(choiceOf),
        });

        if (ok) {
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
            forms.upload.reset();
            clearFieldErrors(forms.upload);
        }

        show(button.dataset.importBack);
    });
});

show('upload');
