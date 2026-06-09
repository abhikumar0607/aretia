document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.case-assign-team-form').forEach((form) => {
        const analystRole = form.querySelector('.case-assign-team-role[data-role="analyst"]');
        const dependentRoles = form.querySelectorAll('.case-assign-team-role[data-requires-analyst]');

        if (!analystRole || dependentRoles.length === 0) {
            return;
        }

        const analystCheckboxes = () => [
            ...analystRole.querySelectorAll('.ms-panel input[type="checkbox"]'),
        ];

        const hasAnalystSelected = () => analystCheckboxes().some((input) => input.checked);

        const refreshMultiSelectLabel = (roleEl) => {
            const wrap = roleEl.querySelector('[data-multi-select]');
            if (!wrap) {
                return;
            }

            const textEl = wrap.querySelector('.ms-trigger-text');
            const inputs = [...wrap.querySelectorAll('.ms-panel input[type="checkbox"]')];
            const checked = inputs.filter((input) => input.checked);
            const placeholder = textEl?.dataset.placeholder || 'Select…';

            if (!textEl) {
                return;
            }

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
                .map((input) => input.closest('.ms-option')?.querySelector('span')?.textContent?.trim())
                .filter(Boolean);
            textEl.textContent = names.length <= 2 ? names.join(', ') : checked.length + ' selected';
        };

        const closePanel = (roleEl) => {
            const wrap = roleEl.querySelector('[data-multi-select]');
            const panel = wrap?.querySelector('.ms-panel');
            const trigger = wrap?.querySelector('.ms-trigger');

            if (panel) {
                panel.hidden = true;
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            wrap?.classList.remove('ms-wrap--open');
        };

        const setDependentRolesEnabled = (enabled) => {
            dependentRoles.forEach((roleEl) => {
                roleEl.classList.toggle('case-assign-team-role--locked', !enabled);

                roleEl.querySelectorAll('.ms-panel input[type="checkbox"]').forEach((input) => {
                    input.disabled = !enabled;
                    if (!enabled) {
                        input.checked = false;
                    }
                });

                roleEl.querySelectorAll('input[type="date"]').forEach((input) => {
                    input.disabled = !enabled;
                    if (!enabled) {
                        input.value = '';
                    }
                });

                const trigger = roleEl.querySelector('.ms-trigger');
                if (trigger) {
                    trigger.disabled = !enabled;
                }

                if (!enabled) {
                    closePanel(roleEl);
                }

                refreshMultiSelectLabel(roleEl);
            });
        };

        const syncDependentRoles = () => {
            setDependentRolesEnabled(hasAnalystSelected());
        };

        analystCheckboxes().forEach((input) => {
            input.addEventListener('change', syncDependentRoles);
        });

        form.addEventListener('submit', (e) => {
            if (hasAnalystSelected()) {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();
            window.showToast?.(
                'error',
                'Analyst is required. Select an Analyst before assigning QA or FQA.',
                { title: 'Required' }
            );

            const trigger = analystRole.querySelector('.ms-trigger');
            const panel = analystRole.querySelector('.ms-panel');
            if (trigger && panel) {
                panel.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
                analystRole.querySelector('[data-multi-select]')?.classList.add('ms-wrap--open');
                trigger.focus();
            }
        }, true);

        syncDependentRoles();
    });
});
