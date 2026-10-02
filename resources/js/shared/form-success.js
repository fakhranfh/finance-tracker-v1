export function showSuccess(form, message) {
    const success = form.querySelector('[data-form-success]');
    if (success) {
        success.textContent = message;
        success.classList.remove('hidden');
    }
}
