export function showSkeleton(formId, skeletonId) {
    document.getElementById(formId)?.classList.add('hidden');
    document.getElementById(skeletonId)?.classList.remove('hidden');
}

export function hideSkeleton(formId, skeletonId) {
    document.getElementById(skeletonId)?.classList.add('hidden');
    document.getElementById(formId)?.classList.remove('hidden');
}

/**
 * Toggle a button between its normal label and a spinner + disabled state.
 * Expects the button to contain [data-button-spinner] and [data-button-label]
 * (the shape rendered by components/atoms/button.blade.php).
 */
export function setButtonLoading(button, isLoading) {
    if (!button) {
        return;
    }

    button.disabled = isLoading;
    button.querySelector('[data-button-spinner]')?.classList.toggle('hidden', !isLoading);
    button.querySelector('[data-button-label]')?.classList.toggle('hidden', isLoading);
}
