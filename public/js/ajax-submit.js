/**
 * Global POST form handling: lock submit buttons immediately, AJAX + toast where enabled.
 */
async function submitPostRequest(url, body, options) {
    options = options || {};
    const res = await fetch(url, {
        method: 'POST',
        body,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        redirect: 'manual',
    });

    const contentType = res.headers.get('content-type') || '';
    let data = {};

    if (contentType.includes('application/json')) {
        try {
            data = await res.json();
        } catch {
            data = {};
        }
    }

    if (data.toast && window.showToast && (!options.silent || data.toast.type === 'error')) {
        window.showToast(data.toast.type, data.toast.message, {
            title: data.toast.title,
            duration: data.toast.duration || 5000,
        });
    }

    if (res.ok || res.status === 0) {
        if (data.redirect && !options.silent) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 550);
            return { ok: true, data };
        }
        if (!data.toast && !options.silent) {
            setTimeout(() => window.location.reload(), 550);
        }
        return { ok: true, data };
    }

    if (!data.toast && window.showToast) {
        window.showToast('error', 'Something went wrong. Please try again.', { title: 'Error' });
    }

    return { ok: false, data };
}

window.submitPostRequest = submitPostRequest;

const AJAX_SKIP_IDS = new Set(['kyc-upload-form', 'kyc-submit-form', 'order-form', 'report-upload-form']);

function formMethod(form) {
    return (form.getAttribute('method') || 'get').toLowerCase();
}

function isSubmittableForm(form) {
    return form instanceof HTMLFormElement && formMethod(form) !== 'get';
}

function shouldSkipGlobalLock(form) {
    if (form.dataset.noSubmitLock !== undefined) return true;
    if (form.hasAttribute('data-binary-upload')) return true;
    if (form.hasAttribute('data-profile-form')) return true;
    if (AJAX_SKIP_IDS.has(form.id)) return true;
    if (form.id === 'case-chat-form') return true;
    return false;
}

function shouldSkipAjax(form) {
    if (form.dataset.noToast !== undefined) return true;
    if (AJAX_SKIP_IDS.has(form.id)) return true;
    if (form.hasAttribute('data-binary-upload')) return true;
    if (form.hasAttribute('data-profile-form')) return true;
    if (form.action && form.action.includes('/logout')) return true;
    return false;
}

function getSubmitButtons(form) {
    const buttons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
    if (form.id) {
        document.querySelectorAll(`button[form="${form.id}"], input[form="${form.id}"]`).forEach((btn) => {
            if (!buttons.includes(btn)) {
                buttons.push(btn);
            }
        });
    }
    return buttons;
}

function lockSubmitButton(btn) {
    if (!btn || btn.disabled) return;
    if (btn.tagName === 'INPUT') {
        if (!btn.dataset.submitOriginalValue) {
            btn.dataset.submitOriginalValue = btn.value;
        }
    } else if (!btn.dataset.submitOriginalHtml) {
        btn.dataset.submitOriginalHtml = btn.innerHTML;
    }
    btn.disabled = true;
    btn.classList.add('is-submitting');
    btn.setAttribute('aria-busy', 'true');
}

function unlockSubmitButton(btn) {
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove('is-submitting');
    btn.removeAttribute('aria-busy');
    if (btn.tagName === 'INPUT' && btn.dataset.submitOriginalValue) {
        btn.value = btn.dataset.submitOriginalValue;
        delete btn.dataset.submitOriginalValue;
    } else if (btn.dataset.submitOriginalHtml) {
        btn.innerHTML = btn.dataset.submitOriginalHtml;
        delete btn.dataset.submitOriginalHtml;
    }
}

function lockFormSubmitButtons(form) {
    getSubmitButtons(form).forEach(lockSubmitButton);
}

function unlockFormSubmitButtons(form) {
    getSubmitButtons(form).forEach(unlockSubmitButton);
}

function releaseFormSubmitLock(form) {
    delete form.dataset.submitting;
    unlockFormSubmitButtons(form);
    if (form.id) {
        document.querySelectorAll(`[data-modal-submit="${form.id}"]`).forEach(unlockSubmitButton);
    }
}

window.lockSubmitForm = lockFormSubmitButtons;
window.unlockSubmitForm = releaseFormSubmitLock;

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!isSubmittableForm(form) || shouldSkipGlobalLock(form)) return;

    if (form.dataset.submitting === '1') {
        e.preventDefault();
        e.stopImmediatePropagation();
        return;
    }

    form.dataset.submitting = '1';
    lockFormSubmitButtons(form);
}, true);

document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!isSubmittableForm(form) || shouldSkipAjax(form)) return;

    e.preventDefault();

    try {
        const result = await submitPostRequest(form.action, new FormData(form));
        if (!result.ok) {
            releaseFormSubmitLock(form);
        }
    } catch {
        releaseFormSubmitLock(form);
    }
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-modal-submit]');
    if (!btn || btn.disabled || btn.classList.contains('is-submitting')) return;
    lockSubmitButton(btn);
}, true);
