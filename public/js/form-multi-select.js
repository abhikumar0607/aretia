document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-multi-select]').forEach((wrap) => {
        const trigger = wrap.querySelector('.ms-trigger');
        const panel = wrap.querySelector('.ms-panel');
        const textEl = wrap.querySelector('.ms-trigger-text');
        const inputs = () => [...wrap.querySelectorAll('.ms-panel input[type="checkbox"]')];
        const min = parseInt(wrap.dataset.min || '1', 10);
        const requiredMessage = wrap.dataset.requiredMessage || 'Please select at least one option.';

        if (!trigger || !panel || !textEl) return;

        const updateLabel = () => {
            const checked = inputs().filter((i) => i.checked);
            const placeholder = textEl.dataset.placeholder || 'Select…';

            if (checked.length === 0) {
                textEl.textContent = placeholder;
                textEl.classList.remove('ms-trigger-text--filled');
                return;
            }

            textEl.classList.add('ms-trigger-text--filled');
            if (checked.length === 1) {
                textEl.textContent = checked[0].closest('.ms-option')?.querySelector('span')?.textContent?.trim() || placeholder;
                return;
            }

            const names = checked
                .map((i) => i.closest('.ms-option')?.querySelector('span')?.textContent?.trim())
                .filter(Boolean);
            textEl.textContent = names.length <= 2
                ? names.join(', ')
                : checked.length + ' selected';
        };

        const close = () => {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            wrap.classList.remove('ms-wrap--open');
        };

        const open = () => {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            wrap.classList.add('ms-wrap--open');
        };

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            if (panel.hidden) open();
            else close();
        });

        inputs().forEach((input) => {
            input.addEventListener('change', updateLabel);
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });

        const form = wrap.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                const count = inputs().filter((i) => i.checked).length;
                if (count < min) {
                    e.preventDefault();
                    window.showToast?.('error', requiredMessage, { title: 'Required' });
                    open();
                }
            });
        }

        updateLabel();
    });
});
