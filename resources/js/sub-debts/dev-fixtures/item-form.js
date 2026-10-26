/**
 * Dev-only autofill fixture for the single sub debt form — realistic values generated entirely in JS
 * (no REST call). Per .ai/rules/dev-autofill.md.
 */

const NAMES = ['Uang muka', 'Cicilan bulan ini', 'Cicilan bulan depan', 'Cicilan ke-3', 'Pelunasan akhir'];
const NOTES = ['Transfer lewat BCA', 'Dibayar tunai', 'Menunggu gajian', 'Dicicil dua kali'];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function dateFromToday(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);

    return date.toISOString().slice(0, 10);
}

export function subDebtItemFormFixture() {
    const amount = randomInt(5, 100) * 10000;
    const isPaid = Math.random() < 0.4;

    return {
        name: randomItem(NAMES),
        amount: String(amount),
        paid_amount: String(isPaid ? amount : randomInt(0, 8) * (amount / 10)),
        due_date: dateFromToday(randomInt(-30, 180)),
        note: randomItem(NOTES),
    };
}
