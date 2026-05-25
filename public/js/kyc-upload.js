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

async function uploadKycFile(form, type, file) {
    const body = new FormData();
    body.append('_token', form.querySelector('[name="_token"]').value);
    body.append('type', type);
    body.append('name', file.name);
    body.append('data', await fileToBase64(file));

    return window.submitPostRequest(form.action, body);
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('kyc-upload-form');
    if (!form) return;

    const btn = form.querySelector('[type="submit"]');
    const originalHtml = btn.innerHTML;

    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-file-name]');
        if (!input) return;

        const show = (file) => {
            if (file && nameEl) {
                nameEl.textContent = file.name;
                zone.classList.add('has-file');
            }
        };

        input.addEventListener('change', () => input.files?.[0] && show(input.files[0]));

        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files?.[0]) {
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                input.files = dt.files;
                show(e.dataTransfer.files[0]);
            }
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idFile = document.getElementById('id_document')?.files?.[0];
        const incFile = document.getElementById('incorporation_document')?.files?.[0];

        if (!idFile || !incFile) {
            window.showToast?.('error', 'Please select both documents.', { title: 'Required' });
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Uploading...';

        try {
            await uploadKycFile(form, 'national_id', idFile);
            btn.textContent = 'Uploading incorporation...';
            await uploadKycFile(form, 'incorporation', incFile);
        } catch {
            window.showToast?.('error', 'Upload failed. Please try again.', { title: 'Error' });
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
});
