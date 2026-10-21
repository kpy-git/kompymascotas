/**
 * Kompy · Ampliar fotos con los dedos (kpyproductzoom). Sin librerías; se carga diferido.
 *
 * 1. En móvil, tocar la foto de la ficha abre la galería grande del tema (antes había que acertar
 *    con el botón de la lupa, que es diminuto).
 * 2. Dentro de la galería: pellizcar con dos dedos amplía, dos toques amplían y vuelven, y con la
 *    foto ampliada se arrastra para moverse. Mientras está ampliada no se pasa de foto sin querer.
 *
 * No toca la galería del tema: le pone `transform` a la imagen de la diapositiva activa y la deja
 * como estaba al cerrar o al cambiar de foto.
 */
(function () {
  'use strict';

  var MAX = (window.kpzMax && +window.kpzMax) || 4;
  var modal = document.getElementById('product-modal');
  if (!modal) {
    return;
  }

  var movil = window.matchMedia('(max-width: 991.98px)');

  // 1. Tocar la foto abre la galería (solo en móvil; en PC el tema ya tiene su lupa).
  var carrusel = document.querySelector('.product__carousel');
  if (carrusel) {
    carrusel.addEventListener('click', function (e) {
      if (!movil.matches || e.target.closest('.carousel-control-prev, .carousel-control-next, button, a')) {
        return;
      }
      var lupa = document.querySelector('.product__zoom');
      if (lupa) {
        lupa.click();
      }
    });
  }

  /** Estado del zoom de la diapositiva que se está viendo. */
  var z = { img: null, escala: 1, x: 0, y: 0, dedos: {}, base: 0, baseEscala: 1, ultimoToque: 0 };

  function imagenActiva() {
    var it = modal.querySelector('.carousel-item.active img') || modal.querySelector('.carousel-item img');
    return it || null;
  }

  function pintar() {
    if (!z.img) {
      return;
    }
    z.img.style.transform = 'translate(' + z.x + 'px,' + z.y + 'px) scale(' + z.escala + ')';
    z.img.style.transition = z.dedos && Object.keys(z.dedos).length ? 'none' : 'transform .15s ease-out';
    modal.classList.toggle('kpz-ampliada', z.escala > 1.02);
  }

  function reiniciar() {
    if (z.img) {
      z.img.style.transform = '';
      z.img.style.transition = '';
    }
    z.img = null;
    z.escala = 1;
    z.x = 0;
    z.y = 0;
    z.dedos = {};
    modal.classList.remove('kpz-ampliada');
  }

  /** Para que la foto no se despegue de la pantalla al arrastrar. */
  function encajar() {
    if (!z.img) {
      return;
    }
    var caja = z.img.getBoundingClientRect();
    var margen = 40;
    var maxX = Math.max(0, (caja.width - z.img.clientWidth) / 2 + margen);
    var maxY = Math.max(0, (caja.height - z.img.clientHeight) / 2 + margen);
    z.x = Math.max(-maxX, Math.min(maxX, z.x));
    z.y = Math.max(-maxY, Math.min(maxY, z.y));
  }

  function distancia(a, b) {
    return Math.hypot(a.x - b.x, a.y - b.y);
  }

  function dedos() {
    var l = [];
    for (var k in z.dedos) {
      if (Object.prototype.hasOwnProperty.call(z.dedos, k)) {
        l.push(z.dedos[k]);
      }
    }
    return l;
  }

  modal.addEventListener('pointerdown', function (e) {
    var img = e.target.closest('.carousel-item img');
    if (!img) {
      return;
    }
    if (z.img !== img) {
      reiniciar();
      z.img = img;
    }
    z.dedos[e.pointerId] = { x: e.clientX, y: e.clientY, x0: e.clientX, y0: e.clientY };
    var lista = dedos();
    if (lista.length === 2) {
      z.base = distancia(lista[0], lista[1]);
      z.baseEscala = z.escala;
    } else if (lista.length === 1) {
      // Dos toques seguidos: amplía y vuelve.
      var ahora = Date.now();
      if (ahora - z.ultimoToque < 300) {
        z.escala = z.escala > 1.02 ? 1 : 2.5;
        z.x = 0;
        z.y = 0;
        pintar();
      }
      z.ultimoToque = ahora;
    }
    if (z.escala > 1.02 || lista.length === 2) {
      e.preventDefault();
    }
  }, { passive: false });

  modal.addEventListener('pointermove', function (e) {
    if (!z.img || !z.dedos[e.pointerId]) {
      return;
    }
    var antes = { x: z.dedos[e.pointerId].x, y: z.dedos[e.pointerId].y };
    z.dedos[e.pointerId].x = e.clientX;
    z.dedos[e.pointerId].y = e.clientY;
    var lista = dedos();

    if (lista.length === 2 && z.base > 0) {
      var ahora = distancia(lista[0], lista[1]);
      z.escala = Math.max(1, Math.min(MAX, z.baseEscala * (ahora / z.base)));
      encajar();
      pintar();
      e.preventDefault();
      return;
    }
    if (lista.length === 1 && z.escala > 1.02) {
      z.x += e.clientX - antes.x;
      z.y += e.clientY - antes.y;
      encajar();
      pintar();
      e.preventDefault();
    }
  }, { passive: false });

  function soltar(e) {
    delete z.dedos[e.pointerId];
    if (z.escala <= 1.02) {
      z.escala = 1;
      z.x = 0;
      z.y = 0;
    }
    pintar();
  }
  modal.addEventListener('pointerup', soltar);
  modal.addEventListener('pointercancel', soltar);

  // Al cambiar de foto o cerrar, la anterior vuelve a su tamaño.
  modal.addEventListener('slide.bs.carousel', reiniciar);
  modal.addEventListener('hidden.bs.modal', reiniciar);

  // Pista, una sola vez por visita: en móvil nadie prueba a pellizcar si no se lo dicen.
  modal.addEventListener('shown.bs.modal', function () {
    if (!movil.matches || sessionStorage.getItem('kpz-pista')) {
      return;
    }
    var pista = document.createElement('div');
    pista.className = 'kpz-pista';
    pista.textContent = window.kpzPista || 'Pellizca para ampliar';
    modal.querySelector('.modal-content').appendChild(pista);
    setTimeout(function () { pista.remove(); }, 2600);
    try {
      sessionStorage.setItem('kpz-pista', '1');
    } catch (err) { /* navegación privada */ }
    if (!z.img) {
      z.img = imagenActiva();
    }
  });
})();
