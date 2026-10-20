document.addEventListener('DOMContentLoaded', () => {
    const divProductAddToCart = document.querySelector('.is_mobile .product__add-to-cart');

    if (!divProductAddToCart) {

        return;
    }

    const wrapperFixed = document.querySelector('.product-actions__wrapper-add-to-cart');
    const btnAddToCart = document.querySelector('button.add-to-cart');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            /* el elemento se ve en pantalla */
            if (entry.isIntersecting) {
                wrapperFixed.classList.remove('btn-fixed');
                btnAddToCart.querySelector('span').textContent = btnAddToCart.dataset.textComplete;
            }
            /* el elemento deja de verse al hacer scroll, sale hacia arriba */
            else /*if (!entry.isIntersecting && entry.boundingClientRect.top < 0)*/ {
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
});