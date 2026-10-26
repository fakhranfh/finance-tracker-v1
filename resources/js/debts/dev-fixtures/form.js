/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/debts/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */

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

export function debtFormFixture() {
    return {
        name: randomWords(2),
        amount: (randomInt(100, 100000) / 100).toFixed(2),
        paid_amount: (randomInt(100, 100000) / 100).toFixed(2),
        due_date: new Date().toISOString().slice(0, 10),
        notes: randomWords(8),
        is_paid: Math.random() > 0.5,
    };
}
