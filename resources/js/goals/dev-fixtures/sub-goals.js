/**
 * Dev-only autofill fixture for the goal form's sub goal rows — generated entirely in JS (no REST call).
 * Splits the goal's target and saved amount over two to four rows so the totals add up.
 * Mirrors config('goal.sub_goal_samples'). Per .ai/rules/dev-autofill.md.
 */

const SAMPLES = [
    { name: 'Mas kawin', note: 'Seperangkat alat salat dan perhiasan.' },
    { name: 'Vendor katering', note: '' },
    { name: 'Gedung pernikahan', note: 'Uang muka gedung.' },
    { name: 'Dekorasi dan pelaminan', note: '' },
    { name: 'Fotografer dan videografer', note: 'Paket pernikahan.' },
    { name: 'Busana pengantin', note: '' },
    { name: 'Undangan dan suvenir', note: '' },
];

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

/** Splits a total into `parts` whole-thousand shares, the last one taking the remainder. */
function split(total, parts) {
    const shares = [];
    let left = total;

    for (let index = 1; index < parts; index += 1) {
        const share = Math.min(left, Math.floor(((left / (parts - index + 1)) * (0.5 + Math.random())) / 1000) * 1000);

        shares.push(share);
        left -= share;
    }

    return [...shares, left];
}

function dateBetween(today, last) {
    const span = Math.max(0, Math.round((last - today) / 86400000));
    const date = new Date(today.getTime() + randomInt(0, span) * 86400000);

    return [date.getFullYear(), date.getMonth() + 1, date.getDate()].map((part) => String(part).padStart(2, '0')).join('-');
}

export function subGoalFixtures({ target, saved, desiredDate }) {
    const count = randomInt(2, 4);
    const samples = [...SAMPLES].sort(() => Math.random() - 0.5).slice(0, count);
    const targets = split(Number(target) || 0, count);
    const savedShares = split(Number(saved) || 0, count);
    const last = desiredDate ? new Date(`${desiredDate}T00:00:00`) : new Date();

    return samples.map((sample, index) => ({
        name: sample.name,
        target_amount: String(targets[index]),
        saved_amount: String(savedShares[index]),
        desired_date: dateBetween(new Date(), last),
        note: sample.note,
    }));
}
