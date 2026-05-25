(function () {
    const DEFAULT_DURATION = 5000;

    const ICONS = {
        success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    };

    function getRoot() {
        let root = document.getElementById('toast-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'toast-root';
            root.setAttribute('aria-live', 'polite');
            document.body.appendChild(root);
        }
        return root;
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    function showToast(type, message, options = {}) {
        const title = options.title || (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice');
        const duration = options.duration ?? DEFAULT_DURATION;
        const root = getRoot();

        const el = document.createElement('div');
        el.className = `toast toast-${type}`;
        el.innerHTML = `
            <div class="toast-icon">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">${ICONS[type] || ICONS.info}</svg>
            </div>
            <div class="toast-body">
                <strong class="toast-title">${escapeHtml(title)}</strong>
                <p class="toast-message">${escapeHtml(message)}</p>
            </div>
            <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
            <div class="toast-progress" style="animation-duration:${duration}ms"></div>
        `;

        const close = () => {
            el.classList.remove('toast-visible');
            el.classList.add('toast-leave');
            setTimeout(() => el.remove(), 300);
        };

        el.querySelector('.toast-close').addEventListener('click', close);
        let timer = setTimeout(close, duration);
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => {
            timer = setTimeout(close, duration);
        });

        root.appendChild(el);
        requestAnimationFrame(() => {
            requestAnimationFrame(() => el.classList.add('toast-visible'));
        });

        return el;
    }

    function handleToastResponse(data, fallbackReload = true) {
        if (data?.toast) {
            showToast(data.toast.type, data.toast.message, {
                title: data.toast.title,
                duration: data.toast.duration || DEFAULT_DURATION,
            });
        }
        if (data?.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 500);
            return true;
        }
        if (fallbackReload) {
            setTimeout(() => window.location.reload(), 500);
            return true;
        }
        return false;
    }

    function flushPendingToasts() {
        const list = window.__toasts || [];
        list.forEach((t) => {
            showToast(t.type, t.message, {
                title: t.title,
                duration: t.duration || DEFAULT_DURATION,
            });
        });
        window.__toasts = [];
    }

    window.showToast = showToast;
    window.handleToastResponse = handleToastResponse;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', flushPendingToasts);
    } else {
        flushPendingToasts();
    }
})();
