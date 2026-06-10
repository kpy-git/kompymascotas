const divMenuBrandList = document.querySelector('.kpy-menu__brands-list');

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.kpy-menu__element').forEach(el => {
		el.addEventListener('mouseover', () => el.classList.add('show'));
		el.addEventListener('mouseout', () => el.classList.remove('show'));
	});

	divMenuBrandList.scroll(0, 0);

	document.querySelectorAll('.kpy-menu__brand-letter').forEach(el => {
		el.addEventListener('click', () => {
			divMenuBrandList.scroll({
				left: 0,
				top: Math.max(0, document.getElementById(`kpy-menu__brand-${el.textContent}`).offsetTop - 10),
				behavior: "smooth"
			});
		});
	});

	// hace el menú flotante al hacer scroll
	document.addEventListener('scroll', () => {
		if (prestashop.page.page_name === 'checkout') {
			return;
		}

		document.getElementById('header').classList.toggle('floating', window.scrollY > 900);
		if (document.querySelector('.header-before')) {
			// esconde el mensaje de la parte superior cuando se muestra el menú flotante
			document.querySelector('.header-before').classList.toggle('d-none', window.scrollY > 900);
		}
	});
});