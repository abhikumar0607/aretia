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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-file-name]');
        if (!input || !nameEl) return;
        const show = () => {
            const files = input.files;
            if (!files?.length) return;
            nameEl.textContent = files.length === 1 ? files[0].name : files.length + ' files';
            zone.classList.add('has-file');
        };
        input.addEventListener('change', show);
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files?.length) {
                input.files = e.dataTransfer.files;
                show();
            }
        });
    });

    document.querySelectorAll('[data-binary-upload]').forEach((form) => {
        const input = form.querySelector('input[type="file"]');
        if (!input) return;

        const btn = form.querySelector('[type="submit"]');
        const btnHtml = btn?.innerHTML;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const file = input.files?.[0];
            if (!file) {
                window.showToast?.('error', 'Please select a file.', { title: 'Required' });
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Uploading...';
            }

            const body = new FormData(form);
            body.append('name', file.name);
            body.append('data', await fileToBase64(file));

            const result = await window.submitPostRequest(form.action, body);

            if (!result.ok && btn) {
                btn.disabled = false;
                btn.innerHTML = btnHtml;
            }
        });
    });

    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        const avatarInput = document.getElementById('profile-avatar-input');
        const preview = document.getElementById('profile-avatar-preview');
        const btn = profileForm.querySelector('[type="submit"]');
        const btnHtml = btn?.innerHTML;

        if (avatarInput && preview) {
            avatarInput.addEventListener('change', () => {
                const file = avatarInput.files?.[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" id="profile-avatar-img">';
                };
                reader.readAsDataURL(file);
            });
        }

        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }

            const body = new FormData(profileForm);
            const file = avatarInput?.files?.[0];
            if (file) {
                body.append('avatar_name', file.name);
                body.append('avatar_data', await fileToBase64(file));
            }

            const result = await window.submitPostRequest(profileForm.action, body);

            if (!result.ok && btn) {
                btn.disabled = false;
                btn.innerHTML = btnHtml;
            }
        });
    }

    const orderForm = document.getElementById('order-form');
    if (orderForm) {
        const fileInput = document.getElementById('order_documents');
        const btn = orderForm.querySelector('[type="submit"]');
        const btnHtml = btn?.innerHTML;

        orderForm.addEventListener('submit', async (e) => {
            const files = fileInput?.files;
            if (!files?.length) return;

            e.preventDefault();

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Submitting...';
            }

            const body = new FormData(orderForm);
            for (let i = 0; i < files.length; i++) {
                body.append(`documents[${i}][name]`, files[i].name);
                body.append(`documents[${i}][data]`, await fileToBase64(files[i]));
            }

            const result = await window.submitPostRequest(orderForm.action, body);

            if (!result.ok && btn) {
                btn.disabled = false;
                btn.innerHTML = btnHtml;
            }
        });
    }

    const reportForm = document.getElementById('report-upload-form');
    if (reportForm) {
        const input = reportForm.querySelector('input[type="file"]');
        const btn = reportForm.querySelector('[type="submit"]');
        const btnHtml = btn?.innerHTML;

        reportForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const file = input?.files?.[0];
            if (!file) {
                window.showToast?.('error', 'Please select a report file.', { title: 'Required' });
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Uploading...';
            }

            const body = new FormData(reportForm);
            body.append('name', file.name);
            body.append('data', await fileToBase64(file));

            const result = await window.submitPostRequest(reportForm.action, body);

            if (!result.ok && btn) {
                btn.disabled = false;
                btn.innerHTML = btnHtml;
            }
        });
    }
});
