import { post } from '../shared/http.js';
import { routes } from '../routes.js';

const root = document.getElementById('verify-email');

if (root) {
    const button = root.querySelector('[data-verify-send]');
    const label = button.querySelector('[data-button-label]');
    const spinner = button.querySelector('[data-button-spinner]');
    const success = root.querySelector('[data-form-success]');
    const error = root.querySelector('[data-form-error]');
    let timer = null;
    let sending = false;

    const show = (el, message) => {
        success.classList.add('hidden');
        error.classList.add('hidden');
        if (el) {
            el.textContent = message;
            el.classList.remove('hidden');
        }
    };

    const startCooldown = (seconds) => {
        clearInterval(timer);
        let remaining = Math.ceil(seconds);

        const tick = () => {
            if (remaining <= 0) {
                clearInterval(timer);
                button.disabled = false;
                label.textContent = root.dataset.labelSend;
                return;
            }
            button.disabled = true;
            label.textContent = root.dataset.labelResend.replace(':seconds', remaining);
            remaining -= 1;
        };

        tick();
        timer = setInterval(tick, 1000);
    };

    startCooldown(Number(root.dataset.cooldown));

    button.addEventListener('click', async () => {
        if (sending) {
            return;
        }

        sending = true;
        show(null);
        button.disabled = true;
        spinner?.classList.remove('hidden');

        try {
            const { ok, status, data } = await post(routes.auth.sendVerification, {});

            if (ok) {
                show(success, data.message);
                startCooldown(data.retry_after ?? 60);
                return;
            }

            show(error, data.message ?? 'Something went wrong.');
            startCooldown(status === 429 ? (data.retry_after ?? 60) : 0);
        } catch {
            show(error, 'Something went wrong.');
            startCooldown(0);
        } finally {
            sending = false;
            spinner?.classList.add('hidden');
        }
    });
}
