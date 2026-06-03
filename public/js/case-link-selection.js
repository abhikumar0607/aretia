document.addEventListener('DOMContentLoaded', () => {
    const bar = document.getElementById('case-link-bar');
    const form = document.getElementById('case-link-form');
    const inputsHost = document.getElementById('case-link-inputs');
    const countEl = document.getElementById('case-link-count');
    const submitBtn = document.getElementById('case-link-submit');
    const clearBtn = document.getElementById('case-link-clear');
    const selectAll = document.getElementById('case-select-all');

    if (!bar || !form || !inputsHost) return;

    const boxes = () => Array.from(document.querySelectorAll('.case-select-checkbox'));

    const selectedIds = () => boxes().filter((cb) => cb.checked).map((cb) => cb.value);

    const sync = () => {
        const ids = selectedIds();
        const n = ids.length;

        inputsHost.innerHTML = '';
        ids.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'case_ids[]';
            input.value = id;
            inputsHost.appendChild(input);
        });

        if (countEl) countEl.textContent = String(n);
        if (submitBtn) submitBtn.disabled = n < 2;
        bar.hidden = n === 0;

        if (selectAll) {
            const all = boxes();
            selectAll.checked = all.length > 0 && all.every((cb) => cb.checked);
            selectAll.indeterminate = n > 0 && n < all.length;
        }
    };

    boxes().forEach((cb) => {
        cb.addEventListener('change', sync);
        cb.addEventListener('click', (e) => e.stopPropagation());
    });

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            boxes().forEach((cb) => {
                cb.checked = selectAll.checked;
            });
            sync();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            boxes().forEach((cb) => {
                cb.checked = false;
            });
            sync();
        });
    }

    form.addEventListener('submit', (e) => {
        if (selectedIds().length < 2) {
            e.preventDefault();
        }
    });

    sync();
});
