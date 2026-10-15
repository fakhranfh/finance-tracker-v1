/**
 * Update a <x-organisms.progress-circle>: the ring and the percentage inside it (0-100, rounded),
 * plus what is going on (a title, a sentence of explanation and an optional detail line).
 * Fields left out keep what they show.
 *
 * @param {HTMLElement} root
 * @param {{ percent?: number, title?: string, description?: string, detail?: string }} state
 */
export function setProgress(root, { percent, title, description, detail } = {}) {
    if (percent !== undefined) {
        const value = Math.min(100, Math.max(0, Math.round(percent)));

        root.querySelector('[data-progress-ring]').setAttribute('stroke-dashoffset', String(100 - value));
        root.querySelector('[data-progress-value]').textContent = `${value}%`;
        root.setAttribute('aria-valuenow', String(value));
    }

    [['title', title], ['description', description], ['detail', detail]].forEach(([part, text]) => {
        if (text !== undefined) {
            root.querySelector(`[data-progress-${part}]`).textContent = text;
        }
    });
}
