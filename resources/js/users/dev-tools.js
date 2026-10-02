/**
 * Dev-only helper that wires up the "Autofill" button rendered on user
 * forms (only when app()->isLocal(), see the Blade views). Fills the
 * given form's named fields from a JS-generated fixture — no REST call.
 * See resources/js/users/dev-fixtures/ and .ai/rules/dev-autofill.md.
 *
 * @param {HTMLFormElement} form
 * @param {() => Record<string, string>} fixture
 */
export function wireDevTools(form, fixture) {
    if (!form) {
        return;
    }

    const autofillButton = document.querySelector('[data-dev-autofill]');

    autofillButton?.addEventListener('click', () => {
        Object.entries(fixture()).forEach(([field, value]) => {
            const input = form.elements.namedItem(field);
            if (input && 'value' in input) {
                input.value = value;
            }
        });
    });
}
