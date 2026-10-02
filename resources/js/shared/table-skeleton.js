/**
 * Toggles between the loading skeleton and the real table wrapper.
 * Passing `flash` (a table flash) also hides its message while loading.
 */
export function createTableSkeleton({ skeleton, wrapper, flash }) {
    return {
        show() {
            flash?.hide();
            skeleton?.classList.remove('hidden');
            wrapper?.classList.add('hidden');
        },

        hide() {
            skeleton?.classList.add('hidden');
            wrapper?.classList.remove('hidden');
        },
    };
}
