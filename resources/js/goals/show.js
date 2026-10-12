import { hideSkeleton } from '../shared/loading-state.js';
import { get } from '../shared/http.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

const root = document.getElementById('goal-show');
const icons = JSON.parse(root?.dataset.icons ?? '{}');
const statusLabels = JSON.parse(root?.dataset.statusLabels ?? '{}');

const field = (name) => root.querySelector(`[data-field="${name}"]`);

function iconNode(name, color) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.5');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('class', 'size-8');
    svg.style.color = color;
    svg.innerHTML = icons[name] ?? '';

    return svg;
}

function colorNode(color) {
    const wrapper = document.createElement('span');
    wrapper.className = 'inline-flex items-center gap-2';

    const swatch = document.createElement('span');
    swatch.className = 'size-4 rounded-full border border-outline-variant';
    swatch.style.backgroundColor = color;

    const hex = document.createElement('span');
    hex.textContent = color.toUpperCase();

    wrapper.append(swatch, hex);

    return wrapper;
}

function render(goal) {
    const target = Number(goal.target_amount);
    const saved = Number(goal.saved_amount);
    const progress = target > 0 ? Math.min(100, Math.round((saved / target) * 100)) : 0;

    field('icon').replaceChildren(iconNode(goal.icon, goal.color));
    field('name').textContent = goal.name;
    field('progress').value = progress;
    field('progress_label').textContent = `${progress}%`;
    field('target_amount').textContent = formatMoney(goal.target_amount);
    field('saved_amount').textContent = formatMoney(goal.saved_amount);
    field('remaining').textContent = formatMoney(Math.max(0, target - saved));
    field('desired_date').textContent = formatDate(goal.desired_date);
    field('status').textContent = statusLabels[goal.status] ?? goal.status;
    field('color').replaceChildren(colorNode(goal.color));
    field('note').textContent = goal.note || '-';
}

if (root) {
    const { ok, data } = await get(routes.goals.show(root.dataset.itemId));

    if (ok) {
        render(data.data);
        hideSkeleton('goal-show', 'goal-show-skeleton');
    } else {
        document.getElementById('goal-show-skeleton').classList.add('hidden');
        document.getElementById('goal-show-missing').classList.remove('hidden');
    }
}
