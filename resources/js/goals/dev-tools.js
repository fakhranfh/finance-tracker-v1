/**
 * Dev-only helper that wires up the "Autofill" button rendered on the
 * goal form (only when app()->isLocal(), see the Blade view). Fills the
 * given form's named fields from a JS-generated fixture — no REST call.
 * See resources/js/goals/dev-fixtures/ and .ai/rules/dev-autofill.md.
 *
 * @param {HTMLFormElement} form
 * @param {() => Record<string, string | boolean>} fixture
 */
export function wireDevTools(form, fixture) {
    if (!form) {
        return;
    }

    const autofillButton = document.querySelector('[data-dev-autofill]');

    autofillButton?.addEventListener('click', () => {
        Object.entries(fixture()).forEach(([field, value]) => {
            const input = form.elements.namedItem(field);

            if (!input) {
                return;
            }

            if (input.type === 'checkbox') {
                input.checked = Boolean(value);
            } else if ('value' in input) {
                input.value = value;
            }
        });
    });
}
