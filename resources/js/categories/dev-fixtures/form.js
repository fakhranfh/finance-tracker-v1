/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/categories/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */

const ICONS = ['tag', 'food', 'transport', 'home', 'cart', 'heart', 'briefcase', 'gift'];
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

export function categoryFormFixture() {
    return {
        name: randomWords(2),
        type: randomItem(['income', 'expense']),
        color: `#${randomInt(0, 0xffffff).toString(16).padStart(6, '0')}`,
        icon: randomItem(ICONS),
    };
}
