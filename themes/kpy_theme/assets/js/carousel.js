function scrollProducts(direction, carouselId) {
    const wrapper = document.querySelector(`.products-horizontal-scroll[data-carousel="${carouselId}"]`);
    if (!wrapper) return;
    const scrollContainer = wrapper.querySelector('.scroll-container');
    if (!scrollContainer) return;

    const scrollAmount = 240;
    scrollContainer.scrollLeft += (direction === 'next') ? scrollAmount : -scrollAmount;
}

document.addEventListener('DOMContentLoaded', function() {
    const wrappers = document.querySelectorAll('.products-horizontal-scroll');
    wrappers.forEach(wrapper => {
        const container = wrapper.querySelector('.scroll-container');
        const prevBtn = wrapper.querySelector('.scroll-btn-prev');
        const nextBtn = wrapper.querySelector('.scroll-btn-next');
        if (!container || !prevBtn || !nextBtn) return;

        function updateButtons() {
            const atStart = container.scrollLeft <= 0;
            const atEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;

            prevBtn.style.opacity = atStart ? '0' : '1';
            prevBtn.style.pointerEvents = atStart ? 'none' : 'auto';

            nextBtn.style.opacity = atEnd ? '0' : '1';
            nextBtn.style.pointerEvents = atEnd ? 'none' : 'auto';
        }

        container.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);
        updateButtons();
    });
});