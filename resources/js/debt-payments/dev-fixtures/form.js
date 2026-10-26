/**
 * Dev-only autofill fixture for the payment form — realistic values generated entirely in JS (no REST call).
 * Picks a real account and category from the form's selects. Per .ai/rules/dev-autofill.md.
 */

import { toDateTimeLocal } from '../../shared/datetime.js';

const NOTES = ['Cicilan bulan ini', 'Bayar lewat transfer', 'Dibayar tunai', 'Setoran pertama', 'Pelunasan sebagian'];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function optionValues(selector) {
    return [...document.querySelectorAll(`${selector} option`)].map((option) => option.value).filter(Boolean);
}

/**
 * @param {number} remaining what is still owed, the most a payment may be
 */
export function debtPaymentFormFixture(remaining) {
    const accounts = optionValues('#account_id');
    const categories = optionValues('#category_id');
    const amount = Math.max(1, Math.min(remaining || 50000, Math.round((Math.random() * 0.6 + 0.2) * (remaining || 50000) / 1000) * 1000 || 1000));

    return {
        account_id: accounts.length > 0 ? randomItem(accounts) : '',
        category_id: categories.length > 0 && Math.random() < 0.5 ? randomItem(categories) : '',
        amount: String(amount),
        recorded_at: toDateTimeLocal(),
        note: randomItem(NOTES),
    };
}
