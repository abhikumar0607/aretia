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

async function uploadKycFile(file) {
    const cfg = window.kycUploadConfig;
    if (!cfg?.uploadUrl) throw new Error('Upload not configured');

    if (file.size > cfg.maxBytes) {
        throw new Error('Each file must be ' + cfg.maxMb + ' MB or smaller.');
    }

    const body = new FormData();
    body.append('_token', cfg.token);
    body.append('type', 'national_id');
    body.append('name', file.name);
    body.append('data', await fileToBase64(file));

    const result = await window.submitPostRequest(cfg.uploadUrl, body, { silent: true });
    if (!result.ok) {
        throw new Error(result.data?.toast?.message || 'Upload failed.');
    }
    return result;
}

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.kycUploadConfig;
    if (!cfg) return;

    let hasAnyDocument = !!cfg.hasAnyDocument;
    const fileInput = document.getElementById('kyc_document');
    const zone = document.querySelector('.kyc-single-dropzone');
    const nameEl = zone?.querySelector('[data-file-name]');

    const clearDropzone = () => {
        if (fileInput) fileInput.value = '';
        if (nameEl) nameEl.textContent = '';
        zone?.classList.remove('has-file');
    };

    if (fileInput && zone) {
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files?.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        fileInput.addEventListener('change', async () => {
            const files = fileInput.files;
            if (!files?.length) return;

            try {
                for (let i = 0; i < files.length; i++) {
                    window.showToast?.('info', 'Uploading ' + files[i].name + '…', { title: 'KYC documents' });
                    await uploadKycFile(files[i]);
                    hasAnyDocument = true;
                }
                window.showToast?.('success', files.length > 1 ? files.length + ' files uploaded.' : 'File uploaded.', { title: 'KYC documents' });
                clearDropzone();
                setTimeout(() => window.location.reload(), 600);
            } catch (err) {
                window.showToast?.('error', err.message || 'Upload failed.', { title: 'Error' });
                clearDropzone();
            }
        });
    }

    const submitForm = document.getElementById('kyc-submit-form');
    if (!submitForm) return;

    const submitBtn = document.getElementById('kyc-submit-btn');
    const submitBtnHtml = submitBtn?.innerHTML;

    submitForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!hasAnyDocument) {
            window.showToast?.('error', 'Please upload at least one Government ID or Company Registration file.', { title: 'Required' });
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting…';
        }

        try {
            const body = new FormData(submitForm);
            await window.submitPostRequest(cfg.submitUrl, body);
        } catch (err) {
            window.showToast?.('error', err.message || 'Could not submit. Please try again.', { title: 'Error' });
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtnHtml;
            }
        }
    });
});
