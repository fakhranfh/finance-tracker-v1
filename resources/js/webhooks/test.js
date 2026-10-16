import { get, getAll, post } from '../shared/http.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { routes } from '../routes.js';

/**
 * "Test webhook" page: wires the panel, it sends a sample payload to the dry-run
 * endpoint, which validates it like a real request but saves nothing.
 */
async function wireTester() {
    const panel = document.getElementById('webhook-tester');
    const webhookId = panel?.dataset.webhookId;

    if (!panel || !webhookId) {
        return;
    }

    const payload = document.getElementById('webhook-test-payload');
    const button = document.getElementById('webhook-test-send');
    const result = document.getElementById('webhook-test-result');

    const accounts = (await getAll(routes.accounts.index)) ?? [];
    const webhook = await get(routes.webhooks.show(webhookId));
    const allowed = webhook.ok ? (webhook.data.data.allowed_ips?.[0]?.split('/')[0] ?? null) : null;
    const allowedIds = webhook.ok ? (webhook.data.data.allowed_account_ids ?? []) : [];
    const allowedTypes = webhook.ok ? (webhook.data.data.allowed_types ?? []) : [];
    const account = accounts.find((item) => allowedIds.includes(item.id)) ?? accounts[0];
    const accountName = account?.name ?? '';
    const type = allowedTypes.find((item) => item !== 'transfer') ?? allowedTypes[0] ?? 'expense';

    payload.value = JSON.stringify({
        type,
        account_name: accountName,
        amount: '12.50',
        note: 'Webhook test',
        recorded_at: new Date().toISOString(),
        ...(allowed ? { ip: allowed } : {}),
    }, null, 2);
    panel.classList.remove('hidden');

    button.addEventListener('click', async () => {
        let body;

        try {
            body = JSON.parse(payload.value);
        } catch {
            result.textContent = 'Invalid JSON.';
            result.classList.remove('hidden');
            return;
        }

        setButtonLoading(button, true);

        try {
            const response = await post(routes.webhooks.test(webhookId), body);
            result.textContent = `${response.status}\n${JSON.stringify(response.data, null, 2)}`;
        } catch {
            result.textContent = 'Something went wrong.';
        } finally {
            result.classList.remove('hidden');
            setButtonLoading(button, false);
        }
    });
}

wireTester();
