document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('order-cancellation-modal');
    if (!dialog) {
        return;
    }

    document.querySelector('.order-header__actions').addEventListener('click', async (e) => {
        if (e.target.matches('.order-cancellation-request') || e.target.matches('.cancel-order-cancellation-request')) {

            const response = await fetch(prestashop.modules.kpycancellationrequest.url_handler, {
                method: 'POST',
                body: new URLSearchParams({
                    'order': e.target.dataset.order,
                    'customer': e.target.dataset.customer,
                    'status': e.target.dataset.status,
                }),
            });

            document.querySelector('.page-loader').classList.add('d-none');

            if (!response.ok) {
                dialog.querySelector('.message').innerText = `Ha ocurrido un problema inesperado y no se procesar la solicitud. Vuelve a intentarlo de nuevo, si el problema persiste contacta con nuestro equipo de atención al cliente`;
            }

            const data = await response.json();

            dialog.querySelector('.message').innerText = data.message;
            dialog.showModal();
        }
    });

    dialog.querySelector('.close-dialog').addEventListener('click', () => {
        dialog.close();
        document.querySelector('.page-loader').classList.add('d-none');
        document.location.reload();
    });
});