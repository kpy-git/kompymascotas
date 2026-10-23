/**
 * Kompy · Las promociones en la cesta.
 *
 * Los vales aplicados los lista PrestaShop (nombre, importe y papelera) encima de «¿Tienes un
 * cupón?», y esa lista **se vuelve a pintar en cada cambio de la cesta**, así que los importes
 * siempre cuadran. Aquí solo se dibuja: cada vale con su icono y su color (2ª unidad en verde,
 * cupón en lila, regalo en violeta, Special Price en crema), y al lado lo que no es un vale (Special
 * Price, un regalo que no ha podido entrar) y el empujón «añade 1 ud.».
 *
 * Y dos detalles de rigor:
 *   - la «2ª unidad» no lleva papelera: entra sola, quitarla no tiene sentido; el «Special Price» de
 *     una línea con regalo, tampoco (devuelve a las unidades de la tarjeta de siempre su precio). El
 *     regalo sí: su papelera quita la versión con regalo (el artículo vuelve a su precio de hoy, con
 *     su Special Price, y el regalo sale de la cesta);
 *   - al quitar un cupón con la papelera, se olvida también el que el cliente eligió en la ficha,
 *     para que no vuelva a entrar solo al cambiar una cantidad.
 *
 * Sin dependencias: si algo falla, la lista de PrestaShop se queda como está y sigue funcionando.
 */
(function () {
  'use strict';

  function datos() {
    var todos = document.querySelectorAll('[data-kpc-datos]');
    var ultimo = todos.length ? todos[todos.length - 1] : null;
    try {
      return ultimo ? JSON.parse(ultimo.textContent) : { vales: {} };
    } catch (e) {
      return { vales: {} };
    }
  }

  /** El id de la regla de carrito de un vale de la lista, por su enlace de quitar. */
  function idDelVale(li) {
    var enlace = li.querySelector('[data-link-action="remove-voucher"]');
    var m = enlace ? /[?&]deleteDiscount=(\d+)/.exec(enlace.getAttribute('href') || '') : null;
    return m ? m[1] : '';
  }

  function dibujar() {
    var d = datos();
    [].slice.call(document.querySelectorAll('.js-cart-voucher, .cart-voucher')).forEach(function (caja) {
      var lista = caja.querySelector('.cart-voucher__list');
      if (lista) {
        [].slice.call(lista.querySelectorAll('.cart-voucher__item')).forEach(function (li) {
          if (li.classList.contains('kpc-vale')) {
            return;
          }
          var vale = (d.vales || {})[idDelVale(li)] || null;
          var tipo = vale ? vale.tipo : 'otro';
          li.classList.add('kpc-vale', 'kpc-vale--' + tipo);
          li.setAttribute('data-kpc-id', idDelVale(li));
          // Con una campaña (Black Friday), el vale trae sus colores: la píldora los lleva.
          if (vale && vale.fondo) {
            li.style.background = vale.fondo;
            li.style.color = vale.color || '';
          }

          var nombre = li.querySelector('.cart-voucher__name');
          if (nombre) {
            if (vale && vale.texto) {
              nombre.textContent = vale.texto;
            }
            var icono = document.createElement('span');
            icono.className = 'kpc-vale__icono';
            icono.setAttribute('aria-hidden', 'true');
            if (tipo === 'regalo') {
              // El regalo, con el mismo icono que la línea de la cesta («1 Gift(s)»).
              var i = document.createElement('i');
              i.className = 'material-icons';
              i.textContent = 'redeem';
              icono.appendChild(i);
            } else {
              icono.textContent = tipo === 'segunda' ? '2ª' : (tipo === 'especial' ? '★' : '%');
            }
            if (vale && vale.fondo) {
              icono.style.background = vale.color || '#fff';
              icono.style.color = vale.fondo;
            }
            nombre.parentNode.insertBefore(icono, nombre);
          }
          // La 2ª unidad y el Special Price de una línea con regalo no llevan papelera: entran solos.
          // El regalo sí: la papelera quita la versión con regalo (el artículo vuelve a su precio de
          // hoy y el regalo sale).
          var papelera = li.querySelector('[data-link-action="remove-voucher"]');
          if (papelera && (tipo === 'segunda' || tipo === 'especial')) {
            papelera.parentNode.removeChild(papelera);
          } else if (papelera && tipo === 'regalo') {
            var pista = (d.textos && d.textos.quitar_regalo) || 'Quitar el regalo: el artículo vuelve a su precio de hoy';
            papelera.setAttribute('title', pista);
            papelera.setAttribute('aria-label', pista);
          }
        });
      }

      // Lo nuestro, justo debajo de los vales y encima de «¿Tienes un cupón?».
      var extra = document.querySelector('[data-kpc-extra]');
      if (extra && extra.parentNode !== caja) {
        var antesDe = caja.querySelector('.cart-voucher-form');
        if (lista && lista.nextSibling) {
          caja.insertBefore(extra, lista.nextSibling);
        } else if (antesDe) {
          caja.insertBefore(extra, antesDe);
        } else {
          caja.appendChild(extra);
        }
      }
      caja.classList.toggle('kpc-con-vales', !!lista || !!extra);
    });
  }

  /** Quitar un cupón con la papelera: que se olvide también el elegido en la ficha. */
  document.addEventListener('click', function (e) {
    var enlace = e.target.closest ? e.target.closest('[data-link-action="remove-voucher"]') : null;
    if (!enlace) {
      return;
    }
    var li = enlace.closest('.cart-voucher__item');
    var d = datos();
    var vale = li ? (d.vales || {})[idDelVale(li)] : null;
    if (!vale || vale.tipo !== 'cupon' || !d.quitar) {
      return;
    }
    var cuerpo = new FormData();
    cuerpo.append('codigo', vale.codigo);
    cuerpo.append('accion', 'quitar');
    try {
      fetch(d.quitar, { method: 'POST', body: cuerpo, credentials: 'same-origin', keepalive: true });
    } catch (err) { /* PrestaShop lo quita igual; solo podría volver al cambiar la cantidad */ }
  }, true);

  /** El empujón suma la unidad con el «+» de su línea, como si el cliente lo hubiera pulsado. */
  document.addEventListener('click', function (e) {
    var boton = e.target.closest ? e.target.closest('[data-kpc-sube]') : null;
    if (!boton) {
      return;
    }
    e.preventDefault();
    var partes = (boton.getAttribute('data-kpc-sube') || '').split('-');
    var campo = [].slice.call(document.querySelectorAll('.js-cart-line-product-quantity')).filter(function (c) {
      var url = c.getAttribute('data-update-url') || '';
      return new RegExp('[?&]id_product=' + partes[0] + '(&|$)').test(url)
        && new RegExp('[?&]id_product_attribute=' + partes[1] + '(&|$)').test(url);
    })[0];
    var grupo = campo ? campo.closest('.quantity-button__group, .input-group') : null;
    var mas = grupo ? grupo.querySelector('.js-increment-button, .js-touchspin-up, .bootstrap-touchspin-up') : null;
    boton.disabled = true;
    if (mas) {
      mas.click();
      return;
    }
    // En el paso de pago no hay «+» en las líneas: se suma por la cesta de PrestaShop y se recarga.
    var ps = window.prestashop || {};
    if (!ps.urls || !ps.urls.pages || !ps.static_token) {
      boton.disabled = false;
      return;
    }
    var cuerpo = 'token=' + encodeURIComponent(ps.static_token) + '&id_product=' + encodeURIComponent(partes[0])
      + '&id_customization=0&id_product_attribute=' + encodeURIComponent(partes[1]) + '&qty=1&add=1&action=update&ajax=1';
    fetch(ps.urls.pages.cart, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: cuerpo, credentials: 'same-origin' })
      .then(function () { window.location.reload(); })
      .catch(function () { boton.disabled = false; });
  });

  // Cada vez que PrestaShop vuelve a pintar la cesta, se vuelve a dibujar.
  var pendiente = 0;
  function luego() {
    clearTimeout(pendiente);
    pendiente = setTimeout(dibujar, 30);
  }
  try {
    if (window.prestashop && typeof window.prestashop.on === 'function') {
      window.prestashop.on('updatedCart', luego);
    }
  } catch (e) { /* queda el vigilante de abajo */ }
  if (window.MutationObserver) {
    var resumen = document.querySelector('.js-cart-summary, .cart-summary, #js-checkout-summary');
    if (resumen) {
      new MutationObserver(luego).observe(resumen, { childList: true, subtree: true });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', dibujar);
  } else {
    dibujar();
  }
})();
