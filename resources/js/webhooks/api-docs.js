import { get, post } from '../shared/http.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { searchableSelect } from '../shared/searchable-select.js';
import { showFieldErrors } from '../shared/field-errors.js';
import { hideSnackbar } from '../shared/error-snackbar.js';
import { routes } from '../routes.js';
import { newNonce, sign } from './signing.js';

/** The current local time as a value for a datetime-local input. */
function localNow() {
    return new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

/**
 * Checks the builder fields the endpoint requires. Returns an `{ field: [message] }` map shaped
 * like a Laravel validation-error response, so it can be passed straight to `showFieldErrors`.
 */
function validateBuilder(section, labels, messages) {
    const errors = {};
    const required = (field, value) => {
        if (value.trim() === '') {
            errors[field] = [messages.required.replace(':field', labels[field])];
        }
    };

    required('try-webhook-id', section.querySelector('[data-try-id]').value);
    required('try-account-name', section.querySelector('[data-try-account]').value);
    required('try-amount', section.querySelector('[data-try-amount]').value);
    required('try-recorded-at', section.querySelector('[data-try-recorded-at]').value);

    if (!errors['try-amount'] && !Number.isInteger(Number(section.querySelector('[data-try-amount]').value))) {
        errors['try-amount'] = [messages.integer.replace(':field', labels['try-amount'])];
    }

    return errors;
}

/** Reads the request body from the builder fields. */
function readBody(section) {
    const payload = {
        type: section.querySelector('[data-try-type]:checked').value,
        account_name: section.querySelector('[data-try-account]').value.trim(),
        amount: Number(section.querySelector('[data-try-amount]').value),
        note: section.querySelector('[data-try-note]').value,
        recorded_at: new Date(section.querySelector('[data-try-recorded-at]').value).toISOString(),
    };
    const ip = section.querySelector('[data-try-ip]').value.trim();

    if (ip !== '') {
        payload.ip = ip;
    }

    return payload;
}

/**
 * Swagger-style "Try it out" for the webhook test endpoint. Signs the body with the webhook
 * token like a real client and sends it to the dry-run endpoint, which saves nothing.
 */
function wireTryItOut(section) {
    const toggle = section.querySelector('[data-try-toggle]');
    const panel = section.querySelector('[data-try-panel]');
    const id = section.querySelector('[data-try-id]');
    const timestamp = section.querySelector('[data-try-timestamp]');
    const nonce = section.querySelector('[data-try-nonce]');
    const recordedAt = section.querySelector('[data-try-recorded-at]');
    const execute = section.querySelector('[data-try-execute]');
    const cancel = section.querySelector('[data-try-cancel]');
    const response = section.querySelector('[data-try-response]');
    const url = section.querySelector('[data-try-url]');
    const code = section.querySelector('[data-try-code]');
    const result = section.querySelector('[data-try-result]');
    const messages = {
        required: section.dataset.requiredMessage,
        integer: section.dataset.integerMessage,
    };
    // Labels are read before searchableSelect moves the webhook label to its search input.
    const labels = Object.fromEntries(['try-webhook-id', 'try-account-name', 'try-amount', 'try-recorded-at'].map((field) => [
        field,
        section.querySelector(`label[for="${field}"]`)?.textContent.replace('*', '').trim() ?? field,
    ]));

    searchableSelect(id);

    toggle.addEventListener('click', () => {
        const opening = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !opening);
        toggle.textContent = opening ? toggle.dataset.closeLabel : toggle.dataset.openLabel;

        if (opening && recordedAt.value === '') {
            recordedAt.value = localNow();
        }
    });

    cancel.addEventListener('click', () => {
        panel.classList.add('hidden');
        toggle.textContent = toggle.dataset.openLabel;
    });

    execute.addEventListener('click', async () => {
        hideSnackbar();

        const errors = validateBuilder(section, labels, messages);

        showFieldErrors(section, errors);

        if (Object.keys(errors).length > 0) {
            return;
        }

        const webhookId = id.value;
        const payload = readBody(section);

        setButtonLoading(execute, true);

        try {
            const lookup = await get(routes.webhooks.show(webhookId));

            if (!lookup.ok) {
                code.textContent = String(lookup.status);
                result.textContent = JSON.stringify(lookup.data, null, 2);
                url.textContent = routes.webhooks.test(webhookId);
                return;
            }

            const token = lookup.data.data.token;
            const timeValue = timestamp.value.trim() || String(Math.floor(Date.now() / 1000));
            const nonceValue = nonce.value.trim() || newNonce();
            const signed = { ...payload, signature: await sign({ ...payload, timestamp: timeValue, nonce: nonceValue }, token) };
            const headers = { 'X-Webhook-Timestamp': timeValue, 'X-Webhook-Nonce': nonceValue };
            const sent = await post(routes.webhooks.test(webhookId), signed, headers);

            url.textContent = `${window.location.origin}${routes.webhooks.test(webhookId)}`;
            code.textContent = String(sent.status);
            result.textContent = `${Object.entries(headers).map(([name, value]) => `${name}: ${value}`).join('\n')}\n\n${JSON.stringify(sent.data, null, 2)}`;
        } catch {
            code.textContent = '';
            result.textContent = 'Something went wrong.';
        } finally {
            response.classList.remove('hidden');
            setButtonLoading(execute, false);
        }
    });
}

document.querySelectorAll('[data-try-it-out]').forEach(wireTryItOut);

if (location.hash) {
    document.querySelector(location.hash)?.setAttribute('open', '');
}
