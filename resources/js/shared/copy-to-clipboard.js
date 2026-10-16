const RESET_DELAY_MS = 1500;

/** Copy text with the async clipboard API, falling back to a hidden selection for non-secure contexts. */
async function copyText(text, input) {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        input.select();

        return document.execCommand('copy');
    }
}

/**
 * Wires every [data-copy-target="<input id>"] button to copy that input's value, briefly
 * swapping its label for data-copied-label.
 */
export function wireCopyButtons(root = document) {
    root.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.copyTarget);

            if (!input?.value || !(await copyText(input.value, input))) {
                return;
            }

            button.textContent = button.dataset.copiedLabel;
            setTimeout(() => {
                button.textContent = button.dataset.copyLabel;
            }, RESET_DELAY_MS);
        });
    });
}
