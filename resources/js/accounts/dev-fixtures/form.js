/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/accounts/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */

const ACCOUNT_NAMES = ['Tunai', 'BCA', 'BRI', 'BNI', 'Mandiri', 'Jago', 'GoPay', 'OVO', 'DANA', 'ShopeePay', 'Tabungan', 'Dana Darurat'];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export function accountFormFixture() {
    return {
        name: randomItem(ACCOUNT_NAMES),
        initial_balance: randomInt(10, 1000) * 1000,
    };
}
