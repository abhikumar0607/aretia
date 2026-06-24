/**
 * Enforce today-or-future on inputs with class "due-date-future-only".
 */
(function () {
    function todayString() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function clampInput(input) {
        const min = todayString();
        input.setAttribute('min', min);

        if (input.value && input.value < min) {
            input.value = min;
            window.showToast?.(
                'warning',
                'Due date must be today or a future date.',
                { title: 'Date adjusted' }
            );
        }
    }

    function bindDueDateInput(input) {
        if (input.dataset.dueDateBound === '1') {
            return;
        }
        input.dataset.dueDateBound = '1';

        clampInput(input);

        ['focus', 'click', 'input', 'change', 'blur'].forEach((eventName) => {
            input.addEventListener(eventName, () => clampInput(input));
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clampInput(input);
            }
        });
    }

    function bindAll(root) {
        (root || document).querySelectorAll('input.due-date-future-only[type="date"]').forEach(bindDueDateInput);
    }

    function validateForm(form) {
        const min = todayString();
        let valid = true;

        form.querySelectorAll('input.due-date-future-only[type="date"]').forEach((input) => {
            if (input.disabled || !input.value) {
                return;
            }
            if (input.value < min) {
                valid = false;
                clampInput(input);
            }
        });

        return valid;
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindAll(document);

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (e) => {
                if (!form.querySelector('input.due-date-future-only[type="date"]')) {
                    return;
                }
                if (!validateForm(form)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    window.showToast?.(
                        'error',
                        'Choose today or a future due date.',
                        { title: 'Invalid date' }
                    );
                }
            }, true);
        });
    });

    window.bindDueDateInputs = bindAll;
    window.clampDueDateInput = clampInput;
})();
