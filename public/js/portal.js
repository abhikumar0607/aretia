document.addEventListener('DOMContentLoaded', () => {
    const settingsToggle = document.getElementById('portal-settings-toggle');
    const settingsDropdown = document.getElementById('portal-user-dropdown');
    const settingsMenu = document.getElementById('portal-user-menu');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationToggle = document.getElementById('notification-bell-toggle');

    const closeSettingsMenu = () => {
        if (!settingsDropdown || !settingsToggle) return;
        settingsDropdown.hidden = true;
        settingsToggle.setAttribute('aria-expanded', 'false');
    };

    const closeNotificationMenu = () => {
        if (!notificationDropdown || !notificationToggle) return;
        notificationDropdown.hidden = true;
        notificationToggle.setAttribute('aria-expanded', 'false');
    };

    if (settingsToggle && settingsDropdown && settingsMenu) {
        settingsToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = !settingsDropdown.hidden;
            closeNotificationMenu();
            settingsDropdown.hidden = open;
            settingsToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
        });

        document.addEventListener('click', (e) => {
            if (!settingsMenu.contains(e.target)) {
                closeSettingsMenu();
            }
        });
    }

    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-file-name]');
        if (!input) return;

        const assignFile = (file) => {
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            if (nameEl) {
                nameEl.textContent = file.name;
            }
            zone.classList.add('has-file');
        };

        input.addEventListener('change', () => {
            if (input.files?.[0]) {
                assignFile(input.files[0]);
            }
        });

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files?.[0]) {
                assignFile(e.dataTransfer.files[0]);
            }
        });
    });
});
