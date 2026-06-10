let slideIndex = 1;
const wrapper = document.querySelector(".slider-wrapper");
let slides = document.querySelectorAll(".slider-slide");
const dots = document.querySelectorAll(".slider-dot");

// Clonar primero y último
const firstClone = slides[0].cloneNode(true);
const lastClone = slides[slides.length - 1].cloneNode(true);

wrapper.appendChild(firstClone);
wrapper.insertBefore(lastClone, slides[0]);

slides = document.querySelectorAll(".slider-slide");

wrapper.style.transform = `translateX(-${slideIndex * 100}%)`;

function updateSlider(animate = true) {
    if (!animate) wrapper.style.transition = "none";
    else wrapper.style.transition = "transform 0.8s cubic-bezier(.77,0,.18,1)";

    wrapper.style.transform = `translateX(-${slideIndex * 100}%)`;

    document.querySelectorAll(".slider-slide").forEach(s => s.classList.remove("active"));
    slides[slideIndex].classList.add("active");

    dots.forEach(dot => dot.classList.remove("active"));
    let realIndex = slideIndex - 1;
    if (realIndex >= dots.length) realIndex = 0;
    if (realIndex < 0) realIndex = dots.length - 1;
    dots[realIndex].classList.add("active");
}

wrapper.addEventListener("transitionend", () => {
    if (slides[slideIndex].isSameNode(firstClone)) {
        slideIndex = 1;
        updateSlider(false);
    }

    if (slides[slideIndex].isSameNode(lastClone)) {
        slideIndex = slides.length - 2;
        updateSlider(false);
    }
});

function nextSlide() {
    slideIndex++;
    updateSlider();
}

function currentSlide(n) {
    slideIndex = n;
    updateSlider();
}

setInterval(nextSlide, 5000);

updateSlider();