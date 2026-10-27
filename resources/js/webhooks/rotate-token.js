import { post } from '../shared/http.js';
import { closeModal, openModal } from '../shared/modal.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { routes } from '../routes.js';

const MODAL_ID = 'rotate-webhook-token-modal';

/** Rotates the token through the REST API, showing a skeleton in place of the token input meanwhile. */
export function wireRotateToken(root, webhookId) {
    const wrapper = root.querySelector('[data-rotate-token]');
    const field = wrapper.querySelector('[data-token-field]');
    const skeleton = wrapper.querySelector('[data-token-skeleton]');
    const confirmButton = wrapper.querySelector('[data-rotate-confirm]');

    const setLoading = (isLoading) => {
        field.classList.toggle('hidden', isLoading);
        skeleton.classList.toggle('hidden', !isLoading);
    };

    wrapper.querySelector('[data-rotate-open]').addEventListener('click', () => openModal(MODAL_ID));

    confirmButton.addEventListener('click', async () => {
        setButtonLoading(confirmButton, true);
        closeModal(MODAL_ID);
        setLoading(true);

        try {
            const { ok, data } = await post(routes.webhooks.rotateToken(webhookId));

            if (ok) {
                field.querySelector('#token').value = data.data.token;
            }
        } finally {
            setLoading(false);
            setButtonLoading(confirmButton, false);
        }
    });
}
