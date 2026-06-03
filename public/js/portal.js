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

    const showDropzoneFiles = (input, nameEl, zone) => {
        const files = input.files;
        if (!files?.length) {
            if (nameEl) {
                nameEl.textContent = '';
                nameEl.classList.remove('dropzone-file-names--multi');
            }
            zone.classList.remove('has-file');
            return;
        }

        if (nameEl) {
            const names = Array.from(files).map((f) => f.name);
            if (names.length === 1) {
                nameEl.textContent = names[0];
                nameEl.classList.remove('dropzone-file-names--multi');
            } else {
                nameEl.textContent = names.join('\n');
                nameEl.classList.add('dropzone-file-names--multi');
            }
        }
        zone.classList.add('has-file');
    };

    const setDroppedFiles = (input, fileList) => {
        if (!fileList?.length) return;
        if (input.multiple) {
            input.files = fileList;
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(fileList[0]);
        input.files = dt.files;
    };

    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-file-name]');
        if (!input) return;

        input.addEventListener('change', () => showDropzoneFiles(input, nameEl, zone));

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            setDroppedFiles(input, e.dataTransfer.files);
            showDropzoneFiles(input, nameEl, zone);
        });
    });
});
