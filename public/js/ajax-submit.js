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
        if (data.toast?.type === 'error') {
            return { ok: false, data };
        }

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

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const result = reader.result;
            resolve(result.includes(',') ? result.split(',')[1] : result);
        };
        reader.onerror = () => reject(new Error('read failed'));
        reader.readAsDataURL(file);
    });
}

async function buildFormBody(form) {
    const body = new FormData(form);

    if (form.id !== 'order-form') {
        return body;
    }

    const fileInput = document.getElementById('order_documents');
    const files = fileInput?.files;
    if (files?.length) {
        for (let i = 0; i < files.length; i++) {
            body.append(`documents[${i}][name]`, files[i].name);
            body.append(`documents[${i}][data]`, await fileToBase64(files[i]));
        }
    }

    return body;
}

const AJAX_SKIP_IDS = new Set(['kyc-upload-form', 'kyc-submit-form', 'report-upload-form']);

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
    if (form.hasAttribute('data-file-download')) return true;
    if (AJAX_SKIP_IDS.has(form.id)) return true;
    if (form.hasAttribute('data-binary-upload')) return true;
    if (form.hasAttribute('data-profile-form')) return true;
    if (form.action && form.action.includes('/logout')) return true;
    if (form.action && form.action.includes('/download')) return true;
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

function closeModalAfterSubmit(form) {
    if (!form.id) return;
    const modalBtn = document.querySelector(`[data-modal-submit="${form.id}"][data-modal-close-after]`);
    const modalId = modalBtn?.getAttribute('data-modal-close-after');
    if (modalId) {
        window.closePortalModal?.(modalId);
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
        const result = await submitPostRequest(form.action, await buildFormBody(form));
        if (result.ok) {
            closeModalAfterSubmit(form);
        } else {
            releaseFormSubmitLock(form);
        }
    } catch {
        releaseFormSubmitLock(form);
    }
});
