import { clearApiToken } from './shared/api-token.js';
import { csrfToken } from './shared/csrf.js';
import { setButtonLoading } from './shared/loading-state.js';
import { routes } from './routes';
import './shared/password-toggle.js';
import './shared/pwa.js';

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-logout-trigger]');

    if (!trigger) {
        return;
    }

    event.preventDefault();
    setButtonLoading(trigger, true);

    try {
        await fetch(routes.auth.logout, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });
    } finally {
        clearApiToken();
        window.location.href = routes.pages.home;
    }
});
