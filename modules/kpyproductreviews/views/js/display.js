// en la página de producto al meter los comentarios con Jquery le asigna los eventos al meterlos
// en la página de opiniones los comentarios se renderizan todos en la plantilla si usar jquery
// usamos la misma lógica que en el módulo (list-comments.js)

document.addEventListener('DOMContentLoaded', () => {
    const comments = document.getElementById('product-comments-list');

    if (!comments) return;

    comments.addEventListener('click', event => {
        if (event.target.closest('.useful-review')) {
            updateCommentUsefulness(event.target.closest('.comment[data-comment]').dataset.comment, 1);

        } else if (event.target.closest('.not-useful-review')) {
            updateCommentUsefulness(event.target.closest('.comment[data-comment]').dataset.comment, 0);
        }
    });
});

const updateCommentUsefulness = async (commentId, usefulness) => {
    const response = await fetch('/module/productcomments/UpdateCommentUsefulness', {
        method: 'POST',
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `id_product_comment=${commentId}&usefulness=${usefulness}`,
    });

    if (response.status === 200) {
        const data = await response.json();
        if (data.success) {
            document.querySelector(`.comment[data-comment="${commentId}"] .useful-review-value`).textContent = data.usefulness;
            document.querySelector(`.comment[data-comment="${commentId}"] .not-useful-review-value`).textContent = data.total_usefulness - data.usefulness;
        }
    }
}