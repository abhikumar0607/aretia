(function () {
    const customValue = 'custom';

    function toggleCustomDates(form) {
        const periodSelect = form.querySelector('[data-portal-period-select]');
        const customDates = form.querySelector('[data-portal-custom-dates]');

        if (!periodSelect || !customDates) {
            return;
        }

        const show = periodSelect.value === customValue;
        customDates.hidden = !show;
        customDates.classList.toggle('is-hidden', !show);
    }

    document.querySelectorAll('[data-portal-period-form]').forEach((form) => {
        const periodSelect = form.querySelector('[data-portal-period-select]');

        if (!periodSelect) {
            return;
        }

        periodSelect.addEventListener('change', () => toggleCustomDates(form));
        toggleCustomDates(form);
    });
})();
