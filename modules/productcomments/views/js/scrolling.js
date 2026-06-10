document.addEventListener('DOMContentLoaded', () => {
    const linkComments = document.getElementById('product-comments-link');

    if (!linkComments) {
        return;
    }

    linkComments.addEventListener('click', (event) => {
        const target = document.getElementById('product-comments-list-header');
        window.scrollTo({
            top: target.offsetTop - (document.getElementById('header').offsetHeight * 2),
            behavior: "smooth"
        });
    });
});