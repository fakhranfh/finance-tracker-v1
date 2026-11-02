export const statusClasses = {
    in_use: ['bg-success/10', 'text-success'],
    overdue: ['bg-warning/10', 'text-warning'],
    done: ['bg-surface-container', 'text-on-surface-variant'],
};

/** Label helpers over the `data-labels` JSON a page receives from `__('cycles.js')`. */
export function createText(labels) {
    const t = (key, vars = {}) => Object.entries(vars).reduce((text, [name, value]) => text.replace(`:${name}`, value), labels[key]);
    const daysText = (n) => (n === 1 ? labels.day : t('days', { n }));

    return { t, daysText };
}
