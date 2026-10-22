

document.addEventListener('DOMContentLoaded', () => {
    /*
    // muestra el boton fijo de añadir al carrito en la version movil cuando deja de verse en pantalla
    const divProductAddToCart = document.querySelector('.is_mobile .product__add-to-cart');

    if (!divProductAddToCart) {

        return;
    }

    const wrapperFixed = document.querySelector('.product-actions__wrapper-add-to-cart');
    const btnAddToCart = document.querySelector('button.add-to-cart');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            /* el elemento se ve en pantalla *
            if (entry.isIntersecting) {
                wrapperFixed.classList.remove('btn-fixed');
                btnAddToCart.querySelector('span').textContent = btnAddToCart.dataset.textComplete;
            }
            /* el elemento deja de verse al hacer scroll, sale hacia arriba *
            else /*if (!entry.isIntersecting && entry.boundingClientRect.top < 0)* {
                wrapperFixed.classList.add('btn-fixed');
                btnAddToCart.querySelector('span').textContent = btnAddToCart.dataset.textAlt;
            }
        });
    });

    observer.observe(divProductAddToCart);

    const footerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            wrapperFixed.style.display = entry.isIntersecting ? 'none' : 'flex';
        });
    });
    footerObserver.observe(document.getElementById('footer'));
    */

    showSelectedCombination();
    prestashop.on('updatedProduct', () => {
        showSelectedCombination();
    });
});

// ajusta el scroll horizontal de las combinaciones para mostrar la selecciona al cargar la página
function showSelectedCombination() {
    document.querySelectorAll('.radio-variants').forEach(list => {
        const selected = list.querySelector('input[type="radio"]:checked');
        const item = selected?.closest('.radio-variant');

        if (!item) return;

        const left = item.offsetLeft;
        const right = left + item.offsetWidth;

        if (left < list.scrollLeft) {
            list.scrollLeft = left;
        } else if (right > list.scrollLeft + list.clientWidth) {
            list.scrollLeft = right - list.clientWidth;
        }
    });
}