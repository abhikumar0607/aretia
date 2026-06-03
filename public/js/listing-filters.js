function openNativeDatePicker(input) {
    if (!input) {
        return;
    }

    if (typeof input.showPicker === 'function') {
        try {
            input.showPicker();
            return;
        } catch {
            // Fall through if the browser blocks showPicker for this gesture.
        }
    }

    input.focus({ preventScroll: true });
}

function wireDateInputWrap(wrap) {
    const input = wrap?.querySelector('input[type="date"]');
    const btn = wrap?.querySelector('[data-date-picker-trigger]');

    if (!input) {
        return;
    }

    input.addEventListener('click', () => openNativeDatePicker(input));

    if (btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openNativeDatePicker(input);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-date-dropdown]').forEach((root) => {
        const isInlinePortalRange = root.hasAttribute('data-portal-inline-dates');
        const trigger = root.querySelector('[data-date-dropdown-trigger]');
        const panel = root.querySelector('[data-date-dropdown-panel]');
        const labelEl = root.querySelector('[data-date-dropdown-label]');
        const dueFrom = root.querySelector('[data-due-from]');
        const dueTo = root.querySelector('[data-due-to]');

        if (!panel) return;

        if (isInlinePortalRange) {
            if (dueFrom && dueTo) {
                const syncMin = () => {
                    if (dueFrom.value) {
                        dueTo.min = dueFrom.value;
                    } else {
                        dueTo.removeAttribute('min');
                    }
                };
                dueFrom.addEventListener('change', syncMin);
                syncMin();
            }

            root.querySelectorAll('.listing-date-dropdown-input-wrap').forEach(wireDateInputWrap);

            return;
        }

        if (!trigger) return;

        const formatDate = (iso) => {
            if (!iso) return '';
            const d = new Date(iso + 'T12:00:00');
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        };

        const updateLabel = () => {
            const from = dueFrom?.value || '';
            const to = dueTo?.value || '';
            let text = 'Date to Date';
            if (from && to) {
                text = `${formatDate(from)} – ${formatDate(to)}`;
            } else if (from) {
                text = `From ${formatDate(from)}`;
            } else if (to) {
                text = `Until ${formatDate(to)}`;
            }
            if (labelEl) labelEl.textContent = text;
            root.classList.toggle('is-active', Boolean(from || to));
        };

        const open = () => {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            root.classList.add('is-open');
        };

        const close = () => {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            root.classList.remove('is-open');
        };

        const toggle = () => (panel.hidden ? open() : close());

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggle();
        });

        root.querySelectorAll('.listing-date-dropdown-input-wrap').forEach(wireDateInputWrap);

        if (dueFrom && dueTo) {
            const syncMin = () => {
                if (dueFrom.value) {
                    dueTo.min = dueFrom.value;
                } else {
                    dueTo.removeAttribute('min');
                }
            };
            dueFrom.addEventListener('change', () => {
                syncMin();
                updateLabel();
            });
            dueTo.addEventListener('change', updateLabel);
            syncMin();
            updateLabel();
        }

        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !panel.hidden) close();
        });
    });
});
