import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const toolbar = [
    ['bold', 'italic', 'underline'],
    [{ header: 2 }, { header: 3 }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote'],
    ['clean'],
];

export function initQuillEditors() {
    document.querySelectorAll('[data-quill]').forEach(container => {
        const targetSelector = container.dataset.target;
        const hiddenInput     = document.querySelector(targetSelector);
        const initialContent  = container.dataset.content || '';

        const quill = new Quill(container, {
            theme:       'snow',
            modules:     { toolbar },
            placeholder: container.dataset.placeholder || '',
        });

        if (initialContent) {
            quill.root.innerHTML = initialContent;
        }

        const form = container.closest('form');
        if (form && hiddenInput) {
            form.addEventListener('submit', () => {
                hiddenInput.value = quill.root.innerHTML;
            });
        }
    });
}
