/**
 * Format an ISO date/time from the API as "dd MMM yyyy, HH:mm" (24-hour) in the
 * viewer's timezone, with the month name in the page locale, e.g. "02 Okt 2026, 14:30".
 */
export function formatDateTime(value) {
    const date = new Date(value);
    const locale = document.documentElement.lang || undefined;
    const day = date.toLocaleDateString(locale, { day: '2-digit', month: 'short', year: 'numeric' });
    const time = [date.getHours(), date.getMinutes()].map((part) => String(part).padStart(2, '0')).join(':');

    return `${day}, ${time}`;
}

/**
 * Convert an ISO date/time to the "YYYY-MM-DDTHH:mm" value of <input type="datetime-local">
 * (viewer's timezone). Without an argument, returns the current time.
 */
export function toDateTimeLocal(value = new Date()) {
    const date = new Date(value);
    const pad = (part) => String(part).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
