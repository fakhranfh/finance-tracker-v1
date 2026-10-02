/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see the Blade organisms). Per .ai/rules/dev-autofill.md.
 */
export function resetPasswordFixture() {
    const password = 'NewPassword123!';

    return {
        password,
        password_confirmation: password,
    };
}
