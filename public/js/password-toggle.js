document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        var inputId = button.getAttribute('data-toggle-password');
        var input = inputId ? document.getElementById(inputId) : null;
        if (!input) {
            return;
        }

        var showIcon = button.querySelector('.auth-password-icon--show');
        var hideIcon = button.querySelector('.auth-password-icon--hide');

        function setVisible(icon, visible) {
            if (!icon) {
                return;
            }
            icon.hidden = !visible;
            icon.style.display = visible ? 'block' : 'none';
        }

        setVisible(showIcon, true);
        setVisible(hideIcon, false);

        button.addEventListener('click', function () {
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            setVisible(showIcon, !isHidden);
            setVisible(hideIcon, isHidden);
        });
    });
});
