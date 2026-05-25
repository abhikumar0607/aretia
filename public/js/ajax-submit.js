/**
 * All POST forms & binary uploads — JSON toast from controller ($request->validate + Toast::back).
 */
async function submitPostRequest(url, body) {
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

    if (data.toast && window.showToast) {
        window.showToast(data.toast.type, data.toast.message, {
            title: data.toast.title,
            duration: data.toast.duration || 5000,
        });
    }

    if (res.ok || res.status === 0) {
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 550);
            return { ok: true, data };
        }
        if (!data.toast) {
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

document.addEventListener('DOMContentLoaded', () => {
    const skip = new Set(['kyc-upload-form', 'order-form', 'report-upload-form']);

    document.querySelectorAll('form[method="post"], form[method="POST"]').forEach((form) => {
        if (form.dataset.noToast !== undefined) return;
        if (skip.has(form.id)) return;
        if (form.hasAttribute('data-binary-upload')) return;
        if (form.hasAttribute('data-profile-form')) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            const btnHtml = btn?.innerHTML;
            if (btn) {
                btn.disabled = true;
            }

            const result = await submitPostRequest(form.action, new FormData(form));

            if (!result.ok && btn) {
                btn.disabled = false;
                btn.innerHTML = btnHtml;
            }
        });
    });
});
