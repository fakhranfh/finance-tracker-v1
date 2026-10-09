/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/records/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */

import { toDateTimeLocal } from '../../shared/datetime.js';

const NOTES = ['Makan siang', 'Bensin motor', 'Gaji bulanan', 'Belanja bulanan', 'Listrik', 'Internet', 'Kopi', 'Parkir', 'Transportasi online', 'Bonus proyek', 'Top up e-wallet', 'Nonton bioskop'];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export function recordFormFixture() {
    const accountIds = [...document.querySelectorAll('#account_id option')].map((option) => option.value).filter(Boolean);
    const type = randomItem(['income', 'expense', 'transfer']);
    const [accountId, toAccountId] = [...accountIds].sort(() => Math.random() - 0.5);

    const categoryIds = [...document.querySelectorAll('#category_id option')]
        .filter((option) => option.value && option.dataset.type === type)
        .map((option) => option.value);

    return {
        type,
        account_id: accountId ?? '',
        to_account_id: type === 'transfer' ? (toAccountId ?? '') : '',
        category_id: categoryIds.length > 0 ? randomItem(categoryIds) : '',
        amount: randomInt(1, 500) * 1000,
        note: randomItem(NOTES),
        recorded_at: toDateTimeLocal(),
    };
}
