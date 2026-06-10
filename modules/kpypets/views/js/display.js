const dialog = document.querySelector('.delete-pet');

const btnDelete = dialog.querySelector('.delete');

if (dialog) {
    document.querySelectorAll('.open-delete').forEach(el => {
        el.addEventListener('click', async (event) => {
            btnDelete.dataset.target = event.target.dataset.target;
            dialog.showModal();
        });
    });

    btnDelete.addEventListener('click', () => {
        window.location.href = btnDelete.dataset.target;
    });

    dialog.querySelector('.close-dialog').addEventListener('click', (e) => {
        dialog.close();
    });

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