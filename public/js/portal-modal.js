document.addEventListener('DOMContentLoaded', () => {
    const openModals = new Set();

    const getModal = (id) => document.getElementById(id);

    const openModal = (id) => {
        const modal = getModal(id);
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        openModals.add(id);
        document.body.classList.add('portal-modal-open');
        const focusTarget = modal.querySelector('[data-modal-focus]') || modal.querySelector('.portal-modal-dialog');
        focusTarget?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        openModals.delete(modal.id);
        if (openModals.size === 0) {
            document.body.classList.remove('portal-modal-open');
        }
    };

    window.closePortalModal = (idOrElement) => {
        closeModal(typeof idOrElement === 'string' ? getModal(idOrElement) : idOrElement);
    };

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const id = trigger.getAttribute('data-modal-open');
            if (id) openModal(id);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(trigger.closest('.portal-modal'));
        });
    });

    document.querySelectorAll('.portal-modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', () => closeModal(backdrop.closest('.portal-modal')));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || openModals.size === 0) return;
        const lastId = [...openModals].pop();
        closeModal(getModal(lastId));
    });

    document.querySelectorAll('[data-modal-submit]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled || btn.classList.contains('is-submitting')) return;

            const formId = btn.getAttribute('data-modal-submit');
            const form = formId ? document.getElementById(formId) : null;
            if (!form) return;

            btn.disabled = true;
            btn.classList.add('is-submitting');
            btn.setAttribute('aria-busy', 'true');

            form.requestSubmit();
        });
    });
});
