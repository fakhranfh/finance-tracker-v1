/**
 * Dev-only autofill fixture for the debt form's sub debt rows — generated entirely in JS (no REST call).
 * Splits the debt's amount over two to four instalments, paying the first ones in full.
 * Per .ai/rules/dev-autofill.md.
 */

const NAMES = ['Uang muka', 'Cicilan bulan ini', 'Cicilan bulan depan', 'Cicilan ke-3', 'Pelunasan akhir'];
const NOTES = ['Transfer lewat BCA', 'Dibayar tunai', 'Menunggu gajian', ''];

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function dateFromToday(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);

    return date.toISOString().slice(0, 10);
}

export function subDebtFixtures({ amount }) {
    const total = Number(amount) || randomInt(5, 500) * 10000;
    const parts = randomInt(2, 4);
    const share = Math.floor(total / parts / 1000) * 1000;
    const paidParts = randomInt(0, parts - 1);

    return Array.from({ length: parts }, (_, index) => {
        const rowAmount = index === parts - 1 ? total - share * (parts - 1) : share;

        return {
            name: NAMES[index] ?? NAMES[NAMES.length - 1],
            amount: String(rowAmount),
            paid_amount: String(index < paidParts ? rowAmount : 0),
            due_date: dateFromToday((index + 1) * 30),
            note: NOTES[randomInt(0, NOTES.length - 1)],
        };
    });
}
