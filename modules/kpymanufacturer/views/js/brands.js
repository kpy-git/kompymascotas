document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.brand-letter[data-letter]').forEach(elLetter => {
        elLetter.addEventListener('click', () => {
            document.querySelectorAll('.brands-container').forEach(elBrand => {
                elBrand.classList.toggle('hidden', elLetter.dataset.letter !== '0' && elLetter.dataset.letter !== elBrand.dataset.letter);
            });
        });
    });
});