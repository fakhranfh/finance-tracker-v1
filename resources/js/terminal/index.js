import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { del, get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';

const skeleton = document.getElementById('terminal-skeleton');
const unlockForm = document.getElementById('terminal-unlock');
const consoleBox = document.getElementById('terminal-console');
const passwordForm = document.getElementById('terminal-password-form');
const savedNotice = document.querySelector('[data-password-saved]');
const commandForm = document.getElementById('terminal-form');
const output = document.querySelector('[data-terminal-output]');
const lockButton = document.querySelector('[data-terminal-lock]');

const forgotButton = document.querySelector('[data-terminal-forgot]');
const forgotNotice = document.querySelector('[data-forgot-notice]');
let resetToken = new URLSearchParams(window.location.search).get('reset');

const history = [];
let historyIndex = 0;

function show(state) {
    skeleton.classList.add('hidden');
    unlockForm.classList.toggle('hidden', state !== 'locked');
    consoleBox.classList.toggle('hidden', state !== 'unlocked');
    lockButton.classList.toggle('hidden', state !== 'unlocked');
    passwordForm.classList.toggle('hidden', state === 'locked');

    const headings = { setup: passwordForm.dataset.setupHeading, reset: passwordForm.dataset.resetHeading };
    const needsCurrent = state !== 'setup' && state !== 'reset';
    passwordForm.querySelector('[data-password-heading]').textContent = headings[state] ?? passwordForm.dataset.changeHeading;
    passwordForm.querySelector('[data-password-hint]').classList.toggle('hidden', state !== 'setup');
    passwordForm.querySelector('[data-current-password-group]').classList.toggle('hidden', ! needsCurrent);
    passwordForm.current_password.required = needsCurrent;

    if (state === 'locked') {
        unlockForm.terminal_password.value = '';
        unlockForm.terminal_password.focus();
    } else if (state === 'unlocked') {
        commandForm.command.focus();
    }
}

function stateOf({ configured, unlocked }) {
    if (! configured) {
        return 'setup';
    }

    return unlocked ? 'unlocked' : 'locked';
}

function append(text, className) {
    const line = document.createElement('p');
    line.className = `whitespace-pre-wrap break-words ${className}`;
    line.textContent = text;
    output.appendChild(line);
    output.scrollTop = output.scrollHeight;
}

async function load() {
    const { ok, data } = await get(routes.terminal.show);

    const state = ok ? stateOf(data.data) : 'locked';

    show(resetToken && state !== 'setup' ? 'reset' : state);
}

unlockForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    clearFieldErrors(unlockForm);
    hideSnackbar();

    const clientErrors = validateForm(unlockForm);
    if (Object.keys(clientErrors).length > 0) {
        showFieldErrors(unlockForm, clientErrors);
        return;
    }

    const submitButton = unlockForm.querySelector('button[type="submit"]');
    setButtonLoading(submitButton, true);

    try {
        const { ok, status, data } = await post(routes.terminal.unlock, { password: unlockForm.terminal_password.value });

        if (ok) {
            unlockForm.terminal_password.value = '';
            show(stateOf(data.data));
        } else if (status === 422 && data.errors) {
            showFieldErrors(unlockForm, { terminal_password: data.errors.password });
        } else {
            showGenericError(unlockForm, data.message ?? unlockForm.dataset.failed, 'terminal_password');
        }
    } catch {
        showGenericError(unlockForm, unlockForm.dataset.failed, 'terminal_password');
    } finally {
        setButtonLoading(submitButton, false);
    }
});

commandForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    clearFieldErrors(commandForm);
    hideSnackbar();

    const clientErrors = validateForm(commandForm);
    if (Object.keys(clientErrors).length > 0) {
        showFieldErrors(commandForm, clientErrors);
        return;
    }

    const command = commandForm.command.value.trim();

    history.push(command);
    historyIndex = history.length;

    if (command === 'clear') {
        output.replaceChildren();
        commandForm.command.value = '';
        return;
    }

    const submitButton = commandForm.querySelector('button[type="submit"]');
    setButtonLoading(submitButton, true);

    try {
        const { ok, status, data } = await post(routes.terminal.run, { command });

        if (ok) {
            append(`$ ${data.data.command}`, 'text-inverse-primary');
            append(data.data.output, '');
            if (data.data.exit_code !== 0) {
                append(consoleBox.dataset.exitCode.replace(':code', data.data.exit_code), 'text-error-container');
            }
            commandForm.command.value = '';
        } else if (status === 423) {
            show('locked');
        } else if (status === 422 && data.errors) {
            showFieldErrors(commandForm, data.errors);
        } else {
            showGenericError(commandForm, data.message ?? consoleBox.dataset.failed, 'command');
        }
    } catch {
        showGenericError(commandForm, consoleBox.dataset.failed, 'command');
    } finally {
        setButtonLoading(submitButton, false);
        commandForm.command.focus();
    }
});

commandForm.command.addEventListener('keydown', (event) => {
    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
        return;
    }

    event.preventDefault();
    historyIndex = Math.min(Math.max(historyIndex + (event.key === 'ArrowUp' ? -1 : 1), 0), history.length);
    commandForm.command.value = history[historyIndex] ?? '';
});

passwordForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    clearFieldErrors(passwordForm);
    hideSnackbar();
    savedNotice.classList.add('hidden');

    const clientErrors = validateForm(passwordForm);
    if (Object.keys(clientErrors).length > 0) {
        showFieldErrors(passwordForm, clientErrors);
        return;
    }

    const submitButton = passwordForm.querySelector('button[type="submit"]');
    setButtonLoading(submitButton, true);

    try {
        const fields = {
            password: passwordForm.password.value,
            password_confirmation: passwordForm.password_confirmation.value,
        };
        const { ok, status, data } = resetToken
            ? await post(routes.terminal.reset, { token: resetToken, ...fields })
            : await put(routes.terminal.password, { current_password: passwordForm.current_password.value, ...fields });

        if (ok) {
            if (resetToken) {
                window.history.replaceState({}, '', window.location.pathname);
                resetToken = null;
            }
            passwordForm.reset();
            show(stateOf(data.data));
            savedNotice.textContent = passwordForm.dataset.saved;
            savedNotice.classList.remove('hidden');
        } else if (status === 422 && data.errors) {
            showFieldErrors(passwordForm, data.errors);
        } else {
            showGenericError(passwordForm, data.message ?? passwordForm.dataset.failed, 'password');
        }
    } catch {
        showGenericError(passwordForm, passwordForm.dataset.failed, 'password');
    } finally {
        setButtonLoading(submitButton, false);
    }
});

forgotButton.addEventListener('click', async () => {
    clearFieldErrors(unlockForm);
    forgotNotice.classList.add('hidden');
    setButtonLoading(forgotButton, true);

    try {
        const { ok, status, data } = await post(routes.terminal.forgot);

        if (ok) {
            forgotNotice.textContent = data.message;
            forgotNotice.classList.remove('hidden');
        } else if (status === 422 && data.errors) {
            showFieldErrors(unlockForm, { terminal_password: Object.values(data.errors)[0] });
        } else {
            showGenericError(unlockForm, data.message ?? unlockForm.dataset.failed, 'terminal_password');
        }
    } catch {
        showGenericError(unlockForm, unlockForm.dataset.failed, 'terminal_password');
    } finally {
        setButtonLoading(forgotButton, false);
    }
});

lockButton.addEventListener('click', async () => {
    setButtonLoading(lockButton, true);

    try {
        await del(routes.terminal.lock);
        output.replaceChildren();
        show('locked');
    } finally {
        setButtonLoading(lockButton, false);
    }
});

load();
