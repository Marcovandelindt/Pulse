export function initModals() {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-modal-open]').forEach(el => {
                el.removeAttribute('data-modal-open');
            });
        }
    });
}
