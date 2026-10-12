/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/goals/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */

const ICONS = ['flag', 'home', 'transport', 'cart', 'briefcase', 'education', 'heart', 'gift'];
const STATUSES = ['active', 'paused', 'reached'];
const WORDS = ['alpha', 'bravo', 'cedar', 'delta', 'ember', 'flint', 'grove', 'harbor', 'indigo', 'jasper'];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function randomWords(count = 2) {
    return Array.from({ length: count }, () => randomItem(WORDS)).join(' ');
}

function futureDate(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);

    return [date.getFullYear(), date.getMonth() + 1, date.getDate()].map((part) => String(part).padStart(2, '0')).join('-');
}

export function goalFormFixture() {
    return {
        name: randomWords(2),
        target_amount: String(randomInt(1, 100) * 1000000),
        saved_amount: String(randomInt(0, 10) * 100000),
        desired_date: futureDate(randomInt(30, 730)),
        color: `#${randomInt(0, 0xffffff).toString(16).padStart(6, '0')}`,
        icon: randomItem(ICONS),
        status: randomItem(STATUSES),
        note: randomWords(8),
    };
}
