/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see resources/views/goals/form.blade.php).
 * Per .ai/rules/dev-autofill.md.
 */


// Mirrors config('goal.samples') so autofill matches the dev-only "Generate" data.
const SAMPLES = [
    { name: 'Laptop Baru', target: 15000000, icon: 'briefcase', color: '#2563EB', note: 'Untuk bekerja dan belajar.' },
    { name: 'Liburan Akhir Tahun', target: 8000000, icon: 'flag', color: '#F97316', note: 'Tiket dan penginapan.' },
    { name: 'Motor Baru', target: 25000000, icon: 'transport', color: '#7C3AED', note: 'Uang muka motor.' },
    { name: 'Dana Pernikahan', target: 50000000, icon: 'heart', color: '#EC4899', note: 'Gedung dan katering.' },
    { name: 'Renovasi Rumah', target: 30000000, icon: 'home', color: '#10B981', note: 'Dapur dan kamar mandi.' },
    { name: 'Kuliah S2', target: 40000000, icon: 'education', color: '#0EA5E9', note: 'Biaya semester pertama.' },
    { name: 'Kado Orang Tua', target: 3000000, icon: 'gift', color: '#F59E0B', note: '' },
    { name: 'Belanja Elektronik', target: 6000000, icon: 'cart', color: '#EF4444', note: '' },
];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function futureDate(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);

    return [date.getFullYear(), date.getMonth() + 1, date.getDate()].map((part) => String(part).padStart(2, '0')).join('-');
}

export function goalFormFixture() {
    const sample = randomItem(SAMPLES);

    return {
        name: sample.name,
        target_amount: String(sample.target),
        saved_amount: String(randomInt(0, sample.target / 100000) * 100000),
        desired_date: futureDate(randomInt(30, 730)),
        color: sample.color,
        icon: sample.icon,
        note: sample.note,
    };
}
