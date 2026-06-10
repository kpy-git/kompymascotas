document.addEventListener('DOMContentLoaded', () => {
    // no podemos usar variables aquí, cuando se cambia de combinación ya no es el mismo elemento del DOM (se ha eliminado el anterior)
    // const dialog = document.querySelector('.gamifications-product-dialog');
    handleDialogClose(document.querySelector('.gamifications-product-dialog'));

    document.body.addEventListener('click', e => {
        if (e.target.closest('.gamifications-product-info')) {
            document.querySelector('.gamifications-product-dialog').showModal();
        } else if (e.target.closest('.close-dialog')) {
            document.querySelector('.gamifications-product-dialog').close();
        }
    });

    // cuando se cambia de combinación
    prestashop.on('updatedProduct', () => {
        handleDialogClose(document.querySelector('.gamifications-product-dialog'))
    });
});

function handleDialogClose(dialog) {
    if (!dialog) {
        return;
    }

    dialog.addEventListener('click', (e) => {
        const dialogDimensions = dialog.getBoundingClientRect();
        if (
            e.clientX < dialogDimensions.left ||
            e.clientX > dialogDimensions.right ||
            e.clientY < dialogDimensions.top ||
            e.clientY > dialogDimensions.bottom
        ) {
            dialog.close();
        }
    });
}