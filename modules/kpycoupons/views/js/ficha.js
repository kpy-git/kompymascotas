/**
 * Kompy · Promociones en la ficha (kpycoupons 3.0.0). Sin librerías; se carga diferido.
 *
 * El bloque bajo el precio lo pinta el servidor en cada refresco de PrestaShop; aquí se hace lo que
 * el servidor no puede sin tocar el tema:
 *   1. la tarjeta «12 kg + 3 kg gratis» en la fila de formatos: el tema recorre sus atributos y no
 *      deja meter una tarjeta más desde un hook, así que se monta aquí con la misma estructura que
 *      las suyas (`li.radio-variant`, el mismo radio `group[…]` del formato) y se marca con el campo
 *      oculto `kpy_regalo=1`, que viaja en el refresco y al añadir a la cesta. Al elegirla, el tema
 *      pide su refresco de siempre y el servidor repinta precio, bloque y etiquetas;
 *   2. la pastilla «Kilos gratis» del bloque sube encima del precio (donde la tenía la ficha de antes);
 *   3. el cupón (poner / quitar): se guarda por `/module/kpycoupons/aplicar` y, si ha ido bien, se
 *      refresca la ficha con `eventType: 'updatedProductQuantity'` (el único con el que `core.js`
 *      manda la cantidad escrita y no la repone al mínimo);
 *   4. la caja verde de la 2ª unidad con una cantidad impar sube la cantidad una unidad;
 *   5. durante una campaña, los colores del bloque pasan al contenedor del precio del tema.
 */
(function () {
  'use strict';

  function bloque() {
    return document.querySelector('[data-kpc-ficha]');
  }

  function datos() {
    var b = bloque();
    var s = b ? b.querySelector('[data-kpc-datos]') : null;
    if (!s) {
      return null;
    }
    try {
      return JSON.parse(s.textContent);
    } catch (e) {
      return null;
    }
  }

  function campo() {
    return document.querySelector('[data-kpc-regalo-campo]');
  }

  function refrescar(ev) {
    var ps = window.prestashop;
    if (!ps || typeof ps.emit !== 'function') {
      return;
    }
    ps.emit('updateProduct', {
      eventType: 'updatedProductQuantity',
      event: ev || null,
      resp: {},
      reason: { productUrl: (ps.urls && ps.urls.pages && ps.urls.pages.product) || '' }
    });
  }

  /**
   * Las tarjetas de regalo en la fila de formatos del tema: una copia de la tarjeta del formato
   * disparador, con la banda «Kilos gratis», el nombre, el precio y el €/kg del regalo. Se vuelven a
   * montar tras cada refresco (PrestaShop sustituye la fila entera).
   */
  function montarTarjetas() {
    var d = datos();
    document.querySelectorAll('li[data-kpc-tarjeta]').forEach(function (li) { li.remove(); });
    document.querySelectorAll('.kpc-tarjeta-origen').forEach(function (li) { li.classList.remove('kpc-tarjeta-origen'); });
    if (!d || !d.tarjetas || !d.tarjetas.length) {
      return;
    }
    d.tarjetas.forEach(function (t) {
      var radio = document.querySelector('.js-product-variants input[name="group[' + t.grupo + ']"][value="' + t.attr + '"]:not([data-kpc-regalo-radio])');
      var original = radio ? radio.closest('li.radio-variant') : null;
      if (!original) {
        return;
      }
      original.classList.add('kpc-tarjeta-origen');
      var li = original.cloneNode(true);
      li.setAttribute('data-kpc-tarjeta', String(t.ipa));
      li.classList.add('kpc-tarjeta', 'kpc-tarjeta--' + t.clase);
      var flags = li.querySelector('.attribute-flags');
      if (flags) {
        // La franja violeta («Kilos gratis»), con su estilo en línea como el chip del servidor
        // (`ficha-chip.tpl`): el tema pinta la franja de cada tarjeta solo con estilo en línea.
        var banda = document.createElement('div');
        banda.className = 'kpc-attr kpc-attr--kpc-regalo kpc-attr--tarjeta';
        banda.setAttribute('style', t.estilo || '');
        banda.textContent = t.banda;
        flags.innerHTML = '';
        flags.appendChild(banda);
      }
      var nombre = li.querySelector('.radio-label');
      if (nombre) {
        nombre.textContent = t.nombre;
      }
      var precio = li.querySelector('.attribute-price');
      if (precio) {
        precio.textContent = t.precio;
      }
      var pvr = li.querySelector('.attribute-pvr');
      if (pvr) {
        pvr.textContent = t.pvpr;
      }
      var kg = li.querySelector('.attribute-unit-price');
      if (kg) {
        // Nuestro €/kg, con la unidad escrita como en las demás tarjetas del tema («€/Kg»).
        var unidad = (kg.textContent.match(/€\/[^\s)]+/) || [])[0];
        kg.textContent = unidad ? t.kg.replace(/€\/[^\s)]+/, unidad) : t.kg;
      }
      var r = li.querySelector('input[type="radio"]');
      if (r) {
        r.setAttribute('data-kpc-regalo-radio', '1');
        r.title = t.titulo;
        r.checked = false;
      }
      original.insertAdjacentElement('afterend', li);
      // El estado que pintó el servidor: con `kpy_regalo=1` y este formato, la elegida es la tarjeta.
      if (r && radio && String(d.regalo) === '1' && String(d.ipa) === String(t.ipa)) {
        radio.checked = false;
        r.checked = true;
        var etiqueta = li.closest('.variant');
        var seleccion = etiqueta ? etiqueta.querySelector(':scope > label .fw-bold') : null;
        if (seleccion) {
          seleccion.textContent = t.titulo;
        }
      }
    });
  }

  /** La pastilla («Kilos gratis») encima del precio, donde la tenía la ficha de antes. */
  function subirPastilla() {
    var b = bloque();
    var pastilla = b ? b.querySelector('[data-kpc-pill]') : null;
    var precios = document.querySelector('.product__prices');
    var fila = precios ? precios.querySelector('.prices__wrapper') : null;
    if (pastilla && fila && pastilla.parentNode !== precios) {
      precios.insertBefore(pastilla, fila);
    }
  }

  /**
   * La capa de campaña: los colores que el bloque trae en línea (`--kpc-esp-bg`, `--kpc-esp-fg`) se
   * copian al contenedor del precio del tema para que su piel amarilla tome los de la campaña.
   */
  function campana() {
    var precios = document.querySelector('.product__prices');
    if (!precios) {
      return;
    }
    var b = precios.querySelector('[data-kpc-ficha][data-kpc-campana]');
    if (!b) {
      precios.removeAttribute('data-kpc-campana');
      precios.style.removeProperty('--kpc-esp-bg');
      precios.style.removeProperty('--kpc-esp-fg');
      return;
    }
    precios.setAttribute('data-kpc-campana', b.getAttribute('data-kpc-campana') || '1');
    ['--kpc-esp-bg', '--kpc-esp-fg'].forEach(function (v) {
      var valor = b.style.getPropertyValue(v);
      if (valor) {
        precios.style.setProperty(v, valor);
      }
    });
  }

  function cupon(boton, ev) {
    if (boton.classList.contains('is-cargando')) {
      return;
    }
    var url = boton.getAttribute('data-kpc-url');
    var codigo = boton.getAttribute('data-kpc-cupon');
    var accion = boton.getAttribute('data-kpc-accion') || 'poner';
    if (!url || !codigo) {
      return;
    }
    boton.classList.add('is-cargando');
    var titulo = boton.querySelector('.kpc-cupon__titulo');
    var antes = titulo ? titulo.textContent : '';
    if (titulo && boton.getAttribute('data-kpc-aplicando')) {
      titulo.textContent = boton.getAttribute('data-kpc-aplicando');
    }
    fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
      body: 'ajax=1&codigo=' + encodeURIComponent(codigo) + '&accion=' + encodeURIComponent(accion)
    }).then(function (r) {
      return r.json();
    }).then(function (j) {
      if (j && j.ok) {
        refrescar(ev);
        return;
      }
      boton.classList.remove('is-cargando');
      if (titulo) {
        titulo.textContent = antes;
      }
    }).catch(function () {
      boton.classList.remove('is-cargando');
      if (titulo) {
        titulo.textContent = antes;
      }
    });
  }

  function unaMas() {
    var cantidad = document.getElementById('quantity_wanted');
    if (!cantidad) {
      return;
    }
    var n = parseInt(cantidad.value, 10);
    if (isNaN(n) || n < 1) {
      n = 1;
    }
    cantidad.value = n + 1;
    cantidad.dispatchEvent(new Event('change', { bubbles: true }));
    cantidad.dispatchEvent(new Event('keyup', { bubbles: true }));
  }

  // Antes de que el tema sirva el formulario (su manejador va en burbuja; este, en captura): la
  // tarjeta de regalo elegida pone `kpy_regalo=1`; cualquier otro formato, 0.
  document.addEventListener('change', function (ev) {
    var r = ev.target;
    if (!r || r.type !== 'radio' || !/^group\[/.test(r.name || '')) {
      return;
    }
    var c = campo();
    if (c) {
      c.value = r.hasAttribute('data-kpc-regalo-radio') ? '1' : '0';
    }
  }, true);

  document.addEventListener('click', function (ev) {
    var boton = ev.target.closest('[data-kpc-cupon]');
    if (boton) {
      ev.preventDefault();
      cupon(boton, ev);
      return;
    }
    var segunda = ev.target.closest('[data-kpc-segunda-mas]');
    if (segunda) {
      ev.preventDefault();
      unaMas();
    }
  });

  function pintar() {
    montarTarjetas();
    subirPastilla();
    campana();
  }

  window.addEventListener('pageshow', function () {
    pintar();
    // Al volver con «Atrás», el navegador repone el campo: si no cuadra con lo pintado, se refresca.
    var b = bloque();
    var c = campo();
    if (b && c && c.value !== b.getAttribute('data-kpc-regalo')) {
      refrescar(null);
    }
  });

  if (window.prestashop && typeof window.prestashop.on === 'function') {
    window.prestashop.on('updatedProduct', pintar);
  }
  pintar();
})();
