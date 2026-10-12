/**
 * Dev-only autofill fixture for the single sub goal form — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the "Autofill" button
 * (see resources/views/sub-goals/item-form.blade.php). Per .ai/rules/dev-autofill.md.
 */

// Mirrors config('goal.sub_goal_samples').
const SAMPLES = [
    { name: 'Mas kawin', note: 'Seperangkat alat salat dan perhiasan.' },
    { name: 'Vendor katering', note: '' },
    { name: 'Gedung pernikahan', note: 'Uang muka gedung.' },
    { name: 'Dekorasi dan pelaminan', note: '' },
    { name: 'Fotografer dan videografer', note: 'Paket pernikahan.' },
];

function randomItem(items) {
    return items[Math.floor(Math.random() * items.length)];
}

export function subGoalItemFixture() {
    const sample = randomItem(SAMPLES);

    return {
        name: sample.name,
        is_done: Math.random() > 0.5,
        note: sample.note,
    };
}
