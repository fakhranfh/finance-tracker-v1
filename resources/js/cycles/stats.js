const DAY = 86400000;

/** Parses a "YYYY-MM-DD" calendar date as local midnight, so it never shifts a day. */
function parse(iso) {
    const [year, month, day] = iso.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function toIso(date) {
    return [date.getFullYear(), date.getMonth() + 1, date.getDate()].map((part) => String(part).padStart(2, '0')).join('-');
}

export const today = () => toIso(new Date());

export function addDays(iso, days) {
    const date = parse(iso);

    date.setDate(date.getDate() + days);

    return toIso(date);
}

export function daysBetween(from, to) {
    return Math.round((parse(to) - parse(from)) / DAY);
}

/**
 * What a single purchase shows: days it lasted (or has been in use), its status and the estimated
 * run-out date. `avgDays` is the average the API computed for the purchase's item type (null until
 * one purchase has finished); the monthly budget numbers all come from the API.
 */
export function entryInfo(entry, avgDays, now = today()) {
    if (entry.finished_at) {
        return { done: true, status: 'done', days: daysBetween(entry.bought_at, entry.finished_at), runsOut: entry.finished_at, daysLeft: null };
    }

    const runsOut = avgDays ? addDays(entry.bought_at, avgDays) : null;
    const daysLeft = runsOut ? daysBetween(now, runsOut) : null;

    return {
        done: false,
        status: daysLeft !== null && daysLeft < 0 ? 'overdue' : 'in_use',
        days: Math.max(0, daysBetween(entry.bought_at, now)),
        runsOut,
        daysLeft,
    };
}
