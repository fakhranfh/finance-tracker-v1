import { post } from './http.js';
import { setButtonLoading } from './loading-state.js';

/**
 * Dev-only "generate sample data" button. The Blade view renders the button
 * only when app()->isLocal() and the route is behind EnsureLocalEnvironment,
 * so with no button in the DOM this binds nothing.
 */
export function createGenerateButton({ button, countInput, url, onGenerated, min = 1, max = 50 }) {
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        const count = Math.min(Math.max(parseInt(countInput?.value, 10) || min, min), max);

        if (countInput) {
            countInput.value = count;
        }

        setButtonLoading(button, true);

        const { ok } = await post(url, { count });

        setButtonLoading(button, false);

        if (ok) {
            await onGenerated(button.dataset.successMessage);
        }
    });
}
