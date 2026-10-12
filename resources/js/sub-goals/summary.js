import { get } from '../shared/http.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

/**
 * Loads the progress summary of a goal's sub goals ({ total, done, target_total }), or null when the request fails.
 */
export async function fetchSubGoalSummary(goalId) {
    const { ok, data } = await get(`${routes.goals.subGoals(goalId)}?per_page=1`);

    return ok ? data.summary : null;
}

/**
 * Fills a template such as "Done :done of :total sub goals (:amount in total)" with the summary values.
 */
export function summaryText(template, summary) {
    return template
        .replace(':done', summary.done)
        .replace(':total', summary.total)
        .replace(':amount', formatMoney(summary.target_total));
}
