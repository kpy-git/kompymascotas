let slideIndex = 1;

const wrapper = document.querySelector(".slider-wrapper");
let slides = document.querySelectorAll(".slider-slide");
const dots = document.querySelectorAll(".slider-dot");

// Clonar primero y último
const firstClone = slides[0].cloneNode(true);
const lastClone = slides[slides.length - 1].cloneNode(true);

wrapper.appendChild(firstClone);
wrapper.insertBefore(lastClone, slides[0]);

// Volvemos a obtener los slides incluyendo los clones
slides = document.querySelectorAll(".slider-slide");

// Posición inicial
wrapper.style.transform = `translateX(-${slideIndex * 100}%)`;

function updateSlider(animate = true) {
    if (!animate) {
        wrapper.style.transition = "none";
    } else {
        wrapper.style.transition =
            "transform 0.8s cubic-bezier(.77,0,.18,1)";
    }

    wrapper.style.transform =
        `translateX(-${slideIndex * 100}%)`;

    // Activar slide actual
    slides.forEach(slide => {
        slide.classList.remove("active");
    });

    // Comprobar que el índice es válido
    if (slides[slideIndex]) {
        slides[slideIndex].classList.add("active");
    }

    // Activar dot correspondiente
    dots.forEach(dot => {
        dot.classList.remove("active");
    });

    let realIndex = slideIndex - 1;

    if (realIndex >= dots.length) {
        realIndex = 0;
    }

    if (realIndex < 0) {
        realIndex = dots.length - 1;
    }

    if (dots[realIndex]) {
        dots[realIndex].classList.add("active");
    }
}

wrapper.addEventListener("transitionend", (event) => {

    // Nos aseguramos de reaccionar solamente a la transición
    // del transform del wrapper
    if (event.propertyName !== "transform") {
        return;
    }

    // Hemos llegado al clon del primer slide
    if (slides[slideIndex] === firstClone) {
        slideIndex = 1;
        updateSlider(false);
        return;
    }

    // Hemos llegado al clon del último slide
    if (slides[slideIndex] === lastClone) {
        slideIndex = slides.length - 2;
        updateSlider(false);
        return;
    }
});

function nextSlide() {
    slideIndex++;
    // Evitar que el índice se salga por algún motivo
    if (slideIndex >= slides.length) {
        slideIndex = 1;
    }
    updateSlider();
}

function currentSlide(n) {
    // n corresponde a los slides reales (1, 2, 3...)
    slideIndex = n;
    updateSlider();
}

setInterval(nextSlide, 5000);

updateSlider();