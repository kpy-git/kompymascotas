document.addEventListener('DOMContentLoaded', () => {
    const selectYear = document.getElementById('order-year');
    const selectMonth = document.getElementById('order-month');

    selectYear.addEventListener('change', () => {
        selectMonth.innerHTML = '';

        /** en js los meses no vienen en orden inverso como en smarty,
         * supongo que como las claves son enteros los ordena automaticamente... */
        for (let [month, name] of Object.entries(prestashop.modules.kpycustomerhistory.orders_history[selectYear.value]).reverse()) {
            selectMonth.appendChild(new Option(name, month));
        }

        loadOrderHistory();
    });

    selectMonth.addEventListener('change', () => {
        loadOrderHistory();
    });

    function loadOrderHistory() {
        document.querySelector('.js-page-loader').classList.remove('d-none');
        window.location.href = `${prestashop.urls.pages.history}?year=${selectYear.value}&month=${selectMonth.value}`;
    }
});
