import { hideSkeleton } from '../shared/loading-state.js';
import { formatDate } from '../shared/datetime.js';
import { del, get, put } from '../shared/http.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';
import { setupEntryModals } from './entry-modals.js';
import { flashNextPage, showFlash } from './flash.js';
import { entryInfo } from './stats.js';
import { createText } from './ui.js';

const page = document.getElementById('cycle-show-page');
const labels = JSON.parse(page.dataset.labels);
const { t, daysText } = createText(labels);

const root = document.getElementById('cycle-show');
const menuWrap = document.getElementById('cycle-menu-wrap');
const menu = document.getElementById('cycle-menu');
const menuToggle = document.getElementById('cycle-menu-toggle');

const typePage = routes.pages.cycleType(page.dataset.group, page.dataset.type);
let entry = null;

const field = (name) => root.querySelector(`[data-field="${name}"]`);

function estimateNode(info) {
    const node = document.createDocumentFragment();
    const date = document.createElement('span');

    date.className = 'font-bold';

    if (!info.runsOut) {
        node.append(labels.estimate_unknown);

        return node;
    }

    date.textContent = formatDate(info.runsOut);
    node.append(`${labels[info.done ? 'finished_label' : 'estimate_label']} `, date);

    if (!info.done) {
        const remaining = Math.abs(info.daysLeft);
        const note = info.daysLeft < 0 ? t('estimate_overdue', { days: daysText(remaining) }) : info.daysLeft === 0 ? labels.estimate_today : t('estimate_days_left', { days: daysText(remaining) });

        node.append(` ${note}`);
    }

    return node;
}

function render() {
    const info = entryInfo(entry, entry.type.avg_days);

    field('title').textContent = `${entry.name} (${formatDate(entry.bought_at)})`;
    field('price').textContent = formatMoney(entry.price);
    field('usage').textContent = info.done ? daysText(info.days) : t('usage_running', { days: daysText(info.days) });
    field('estimate').replaceChildren(estimateNode(info));
    menu.querySelector('[data-menu-action="finish"]').classList.toggle('hidden', info.done);
}

function setMenu(open) {
    menu.classList.toggle('hidden', !open);
    menuToggle.setAttribute('aria-expanded', String(open));
}

function setupMenu(modals) {
    const actions = {
        edit: () => modals.openEdit(entry),
        finish: () => modals.openFinish(entry),
        delete: () => modals.openDelete(entry),
    };

    menuToggle.addEventListener('click', () => setMenu(menu.classList.contains('hidden')));
    menu.addEventListener('click', (event) => {
        const item = event.target.closest('[data-menu-action]');

        if (item) {
            setMenu(false);
            actions[item.dataset.menuAction]();
        }
    });
    document.addEventListener('click', (event) => {
        if (!menuWrap.contains(event.target)) {
            setMenu(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenu(false);
        }
    });
}

/** Takes the purchase an update returned (it carries the type's average days too) and redraws the page. */
function applyResponse(response, message) {
    if (response.ok) {
        entry = response.data.data;
        render();
        showFlash(message);
    }

    return response;
}

const { ok, data } = await get(routes.cycles.entry(page.dataset.id));
const belongsHere = ok && String(data.data.type.id) === page.dataset.type && String(data.data.type.category_id) === page.dataset.group;

document.getElementById('cycles-back').href = belongsHere ? typePage : routes.pages.cycles;

if (belongsHere) {
    entry = data.data;

    const modals = setupEntryModals({
        labels,
        async onSave(values) {
            return applyResponse(await put(routes.cycles.entry(entry.id), values), labels.flash_updated);
        },
        async onFinish(target, finishedAt) {
            return applyResponse(await put(routes.cycles.finishEntry(target.id), { finished_at: finishedAt }), labels.flash_finished);
        },
        async onDelete(target) {
            const response = await del(routes.cycles.entry(target.id));

            if (response.ok) {
                flashNextPage(labels.flash_deleted);
                window.location.href = typePage;
            }

            return response;
        },
    });

    setupMenu(modals);
    render();
    hideSkeleton('cycle-show', 'cycle-show-skeleton');
    menuWrap.classList.remove('hidden');
} else {
    document.getElementById('cycle-show-skeleton').classList.add('hidden');
    document.getElementById('cycle-show-missing').classList.remove('hidden');
}
