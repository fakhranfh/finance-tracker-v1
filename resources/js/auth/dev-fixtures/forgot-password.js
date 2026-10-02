import { randomEmail, randomName } from './random.js';

/**
 * Dev-only autofill fixture — plausible fake values generated entirely in
 * JS (no REST call). Only wired up when app()->isLocal() renders the
 * "Autofill" button (see the Blade organisms). Per .ai/rules/dev-autofill.md.
 */
export function forgotPasswordFixture() {
    return {
        email: randomEmail(randomName()),
    };
}
