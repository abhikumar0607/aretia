<div id="delete-account-modal" class="portal-modal" hidden aria-hidden="true">
    <div class="portal-modal-backdrop" data-modal-close aria-hidden="true"></div>
    <div class="portal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-account-modal-title" tabindex="-1" data-modal-focus>
        <div class="portal-modal-icon portal-modal-icon-danger" aria-hidden="true">
            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h3 id="delete-account-modal-title">Delete account?</h3>
        <p>
            Permanently delete <strong data-delete-account-name>this user</strong>?
            This cannot be undone.
        </p>
        <div class="portal-modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm" data-delete-account-confirm form="">Delete account</button>
        </div>
    </div>
</div>
