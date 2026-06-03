(function () {
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('delete-account-modal');
        if (!modal) {
            return;
        }

        const nameEl = modal.querySelector('[data-delete-account-name]');
        const confirmBtn = modal.querySelector('[data-delete-account-confirm]');
        const openModals = new Set();

        const openModal = () => {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            openModals.add(modal.id);
            document.body.classList.add('portal-modal-open');
            (modal.querySelector('[data-modal-focus]') || modal.querySelector('.portal-modal-dialog'))?.focus();
        };

        const closeModal = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            openModals.delete(modal.id);
            document.body.classList.remove('portal-modal-open');
            if (confirmBtn) {
                confirmBtn.setAttribute('form', '');
            }
        };

        document.querySelectorAll('[data-delete-account-open]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();

                const formId = trigger.getAttribute('data-delete-form');
                const userName = trigger.getAttribute('data-user-name') || 'this user';

                if (!formId || !document.getElementById(formId)) {
                    return;
                }

                if (nameEl) {
                    nameEl.textContent = userName;
                }

                if (confirmBtn) {
                    confirmBtn.setAttribute('form', formId);
                }

                openModal();
            });
        });

        modal.querySelectorAll('[data-modal-close]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal();
            });
        });

        modal.querySelector('.portal-modal-backdrop')?.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    });
})();
