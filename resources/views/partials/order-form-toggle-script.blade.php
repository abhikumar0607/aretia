<script>
function toggleCustom() {
    const sel = document.getElementById('package-select');
    const opt = sel.options[sel.selectedIndex];
    const isCustom = opt.dataset.custom === '1';
    const standard = document.getElementById('standard-fields');
    const custom = document.getElementById('custom-fields');

    standard.style.display = isCustom ? 'none' : 'block';
    custom.style.display = isCustom ? 'block' : 'none';

    standard.querySelectorAll('input, select, textarea').forEach((el) => {
        el.disabled = isCustom;
    });
    custom.querySelectorAll('input, select, textarea').forEach((el) => {
        el.disabled = !isCustom;
    });
}
toggleCustom();
</script>
