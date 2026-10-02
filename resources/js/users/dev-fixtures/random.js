/**
 * Random fake-data generators shared by the dev-only autofill fixtures.
 * Per .ai/rules/dev-autofill.md.
 */

const FIRST_NAMES = ['Ahmad', 'Budi', 'Citra', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hana', 'Indra', 'Joko'];
const LAST_NAMES = ['Saputra', 'Wijaya', 'Pratama', 'Kusuma', 'Santoso', 'Hidayat', 'Permata', 'Lestari', 'Utami', 'Nugroho'];

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
