document.addEventListener('DOMContentLoaded', () => {
    const track = document.querySelector('.slider-track');

    if (track) {
        const slides = document.querySelectorAll('.slide');
        const container = document.getElementById('promo-slider');
        const dotsContainer = document.getElementById('dots-container');

        let index = 0;
        let interval;

        // 1. Crear los puntos dinámicamente según el número de imágenes
        slides.forEach((_, i) => {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        });

        const dots = document.querySelectorAll('.dot');

        // 2. Función para actualizar la clase 'active' de los puntos
        function updateDots() {
            dots.forEach(dot => dot.classList.remove('active'));
            dots[index].classList.add('active');
        }

        function updateSlider() {
            // Medimos el ancho del contenedor en este preciso instante
            const slideWidth = container.offsetWidth;

            // Movemos la pista multiplicando el índice por el ancho medido
            track.style.transform = `translateX(-${index * slideWidth}px)`;

            updateDots(); // Función de los puntos (la misma de antes)
        }

        // Evento para que, si cambias el tamaño de la ventana, el slider no se descuadre
        window.addEventListener('resize', updateSlider);

        function goToSlide(n) {
            index = n;
            updateSlider();
        }

        function moveNext() {
            index = (index + 1) % slides.length;
            updateSlider();
        }

        function movePrev() {
            index = (index - 1 + slides.length) % slides.length;
            updateSlider();
        }

        // 3. Control de Pausa y Auto-play
        function startInterval() {
            interval = setInterval(moveNext, 4000); // 4 segundos para que dé tiempo a leer
        }

        function stopInterval() {
            clearInterval(interval);
        }

        container.addEventListener('mouseenter', stopInterval);
        container.addEventListener('mouseleave', startInterval);

        document.querySelector('.prev-btn').addEventListener('click', () => movePrev());
        document.querySelector('.next-btn').addEventListener('click', () => moveNext());

        startInterval();
    }
});