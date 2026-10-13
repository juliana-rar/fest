/**
 * Forms with [data-confirm] ask for confirmation in the native <dialog>
 * before submitting (progressive enhancement: without JS they just submit).
 */
export function initConfirm() {
    const dialog = document.getElementById('confirmar');
    if (!dialog) return;

    const message = dialog.querySelector('p');
    let pending = null;

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;
        event.preventDefault();
        pending = form;
        message.textContent = form.dataset.confirm;
        dialog.returnValue = '';
        dialog.showModal();
    });

    dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'ok' && pending) {
            pending.submit(); // native submit: does not fire the submit event again
        }
        pending = null;
    });
}
