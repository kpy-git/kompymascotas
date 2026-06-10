document.addEventListener('DOMContentLoaded', () => {

    showInPageContactOrderSelectIfNeeded();
});

function showInPageContactOrderSelectIfNeeded() {
    if (prestashop?.page?.page_name === 'contact') {
        const selectContact = document.querySelector('select[name="id_contact"]');
        const selectOrders = document.querySelector('select[name="id_order"]');

        // si el usuario no está logueado no aparece nunca el select, evitamos errores en la consola
        if (selectOrders) {
            selectContact.addEventListener('change', (e) => {
                selectOrders.closest('.kpy-form-group').classList.toggle('d-none', e.target.value === "6");
                if (selectOrders.value === "6") {
                    selectOrders.value = "";
                }
            });
        }
    }
}