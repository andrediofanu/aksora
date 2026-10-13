import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Declarative modal triggers: <button data-open-modal="id"> / <button data-dismiss-modal="id">
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-open-modal]');
    if (opener) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: opener.dataset.openModal }));
    }

    const closer = event.target.closest('[data-dismiss-modal]');
    if (closer) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: closer.dataset.dismissModal }));
    }
});

Alpine.start();
