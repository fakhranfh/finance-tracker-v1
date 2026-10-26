/**
 * Dev-only autofill fixture — realistic Indonesian values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/debts/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */

const NAMES = ['Budi Santoso', 'Siti Rahmawati', 'Agus Pratama', 'Dewi Lestari', 'Rizky Ramadhan', 'Putri Anggraini', 'Hendra Wijaya', 'Nur Aisyah', 'Dimas Saputra', 'Rina Marlina'];
const NOTES = {
    debt: ['Pinjaman untuk bayar kuliah', 'Pinjam untuk biaya berobat', 'Talangan bayar kontrakan', 'Pinjaman modal usaha'],
    receivable: ['Dipinjam untuk biaya sekolah anak', 'Talangan makan bareng teman kantor', 'Pinjaman modal jualan online', 'Dipinjam buat beli tiket mudik'],
};

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

/**
 * @param {'debt' | 'receivable'} type
 */
export function debtFormFixture(type = 'debt') {
    const amount = randomInt(5, 500) * 10000;
    const isPaid = Math.random() < 0.3;

    return {
        name: randomItem(NAMES),
        amount: String(amount),
        paid_amount: String(isPaid ? amount : randomInt(0, 8) * (amount / 10)),
        due_date: dateFromToday(randomInt(-30, 180)),
        notes: randomItem(NOTES[type] ?? NOTES.debt),
        is_paid: isPaid,
    };
}
