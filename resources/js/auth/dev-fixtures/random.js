/**
 * Random fake-data generators shared by the dev-only autofill fixtures.
 * Per .ai/rules/dev-autofill.md.
 */

const FIRST_NAMES = ['Jane', 'John', 'Maria', 'Ahmad', 'Siti', 'Budi', 'Amanda', 'Kevin'];
const LAST_NAMES = ['Doe', 'Smith', 'Santoso', 'Wijaya', 'Pratama', 'Lee', 'Garcia'];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

export function randomName() {
    return `${randomItem(FIRST_NAMES)} ${randomItem(LAST_NAMES)}`;
}

export function randomEmail(name) {
    const slug = name.toLowerCase().replace(/\s+/g, '.');
    const suffix = Math.floor(Math.random() * 10000);

    return `${slug}${suffix}@example.com`;
}
