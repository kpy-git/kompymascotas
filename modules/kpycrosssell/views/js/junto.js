/**
 * Kompy · «Caprichitos»
 *
 * Colocar el bloque, que el «+» de cada tarjeta la añada directo a la cesta y la deje en verde, y
 * que al volver con «Atrás» de la ficha de una sugerencia todo siga como estaba. Sin dependencias:
 * el bloque tiene que funcionar aunque algún otro script de la ficha falle.
 */
(function () {
  'use strict';

  var caja = document.querySelector('[data-kcs]');
  if (!caja) {
    return;
  }

  /* ---------------------------------------------------------------------------------------------
   * Dónde va el bloque.
   *
   * En el móvil se queda donde está, dentro de la caja de compra, que es como funciona bien. En el
   * ordenador la caja de compra es una columna estrecha y tres tarjetas ahí dentro se aprietan, así
   * que el bloque sale de la columna y se pone debajo de la ficha entera, a todo el ancho: una
   * tarjeta bajo la foto, otra en medio y otra bajo el botón de comprar.
   *
   * Se hace desde aquí, con JavaScript, para no tener que tocar la plantilla del tema. Si algún día
   * se aplica el parche del tema, el bloque ya nace fuera de la columna y esto no mueve nada.
   */
  (function colocacion() {
    var fila = document.querySelector('.js-product-container');
    if (!fila || !fila.parentNode || !fila.contains(caja)) {
      return;
    }
    // Una marca invisible para saber volver al sitio de origen cuando se estrecha la ventana.
    var ancla = document.createComment('kcs');
    caja.parentNode.insertBefore(ancla, caja);

    var ancho = window.matchMedia('(min-width: 992px)');

    /**
     * El bloque de puntos (el lila) va pegado al botón de comprar, encima de las tarjetas.
     *
     * Al cambiar de formato, la tienda vuelve a pintar ese bloque con los puntos del formato nuevo,
     * y el que se movió antes se queda donde estaba: por eso salían dos. Así que cada vez se busca
     * el recién pintado —el que todavía no lleva nuestra marca—, se tira el anterior y se coloca.
     */
    function colocarPuntos() {
      var todos = [].slice.call(document.querySelectorAll('.gamifications-product-points'));
      if (!todos.length) {
        return;
      }
      var nuevos = todos.filter(function (p) { return !p.classList.contains('kcs-puntos--caja'); });
      var bueno = nuevos.length ? nuevos[nuevos.length - 1] : todos[0];
      todos.forEach(function (p) {
        if (p !== bueno && p.parentNode) {
          p.parentNode.removeChild(p);
        }
      });
      var comprar = document.querySelector('#kpb-caja .kpb-comprar');
      if (ancho.matches && comprar && bueno.parentNode !== comprar.parentNode) {
        comprar.parentNode.insertBefore(bueno, comprar.nextSibling);
        bueno.classList.add('kcs-puntos--caja');
      }
    }

    function colocar() {
      if (ancho.matches) {
        if (caja.previousSibling !== fila) {
          fila.parentNode.insertBefore(caja, fila.nextSibling);
        }
        caja.classList.add('kcs--ancho');
      } else {
        if (ancla.parentNode && caja.previousSibling !== ancla) {
          ancla.parentNode.insertBefore(caja, ancla.nextSibling);
        }
        caja.classList.remove('kcs--ancho');
      }
      colocarPuntos();
    }

    colocar();
    if (typeof ancho.addEventListener === 'function') {
      ancho.addEventListener('change', colocar);
    } else if (typeof ancho.addListener === 'function') {
      ancho.addListener(colocar);
    }
    // Cuando la tienda repinta el producto (otro formato), hay que volver a colocar los puntos.
    try {
      if (window.prestashop && typeof window.prestashop.on === 'function') {
        window.prestashop.on('updatedProduct', function () { setTimeout(colocarPuntos, 60); });
      }
    } catch (e) { /* sin avisos, el bloque sigue funcionando */ }
  })();

  /* ---------------------------------------------------------------------------------------------
   * El «+»: añade directo a la cesta.
   *
   * Sin pasos en medio: un toque, se añade, y la tarjeta se queda en verde con un ✓. Mientras va,
   * el botón da vueltas y no admite otro toque (así no se añade dos veces). Si falla, vuelve a su
   * «+» y se dice por qué. La cesta de arriba se actualiza sin abrir la ventana de «Producto
   * añadido», que aquí sobra: el verde ya lo dice.
   */
  var aviso = caja.querySelector('[data-kcs-aviso]');
  var url = caja.getAttribute('data-kcs-url') || '';
  var ref = (caja.getAttribute('data-kcs-principal') || '').split('-')[0] || '';
  var borrarAviso = 0;

  var T = (function () {
    var textos = caja.querySelector('[data-kcs-textos]');
    try { return JSON.parse(textos.textContent); } catch (e) { return {}; }
  })();

  function decir(mensaje, mal) {
    clearTimeout(borrarAviso);
    aviso.textContent = mensaje || '';
    aviso.classList.toggle('is-mal', !!mal);
    // Lo bueno se va solo; el error se queda hasta el siguiente intento.
    if (mensaje && !mal) {
      borrarAviso = setTimeout(function () { aviso.textContent = ''; }, 4000);
    }
  }

  function avisarCesta(id, ipa, respuesta) {
    try {
      if (window.prestashop && typeof window.prestashop.emit === 'function') {
        window.prestashop.emit('updateCart', {
          reason: { idProduct: id, idProductAttribute: ipa, linkAction: 'kcs-anadir' },
          resp: respuesta,
        });
      }
    } catch (e) { /* si no se puede avisar, la cesta se ve al recargar */ }
  }

  /* ---------------------------------------------------------------------------------------------
   * Por peso (antiparasitarios). La tarjeta trae un selector con los pesos que encajan con el
   * formato que se está mirando arriba. Si solo encaja uno, ya viene puesto; si encajan varios,
   * hay que elegir antes de añadir: en pastillas y pipetas el peso es la dosis.
   */
  function pesosDe(tarjeta) {
    if (tarjeta.kcsPesos === undefined) {
      try { tarjeta.kcsPesos = JSON.parse(tarjeta.getAttribute('data-kcs-pesos') || 'null'); } catch (e) { tarjeta.kcsPesos = null; }
    }
    return tarjeta.kcsPesos;
  }

  function pintarPrecio(tarjeta, texto, antesTxt, desde) {
    var precio = tarjeta.querySelector('.kcs__precio b');
    var antes = tarjeta.querySelector('.kcs__precio s');
    precio.textContent = '';
    if (desde) {
      var pequeno = document.createElement('small');
      pequeno.textContent = T.desde || '';
      precio.appendChild(pequeno);
      precio.appendChild(document.createTextNode(' '));
    }
    precio.appendChild(document.createTextNode(texto));
    if (antes) {
      antes.textContent = antesTxt || '';
      antes.hidden = !antesTxt;
    }
  }

  function ponerPeso(tarjeta, ipa) {
    var pesos = pesosDe(tarjeta);
    var boton = tarjeta.querySelector('[data-kcs-mas]');
    boton.value = (boton.value || '').split('-')[0] + '-' + (ipa || 0);
    tarjeta.classList.remove('is-falta');
    var d = pesos && ipa ? pesos.datos[ipa] : null;
    if (d) {
      pintarPrecio(tarjeta, d.precio_txt, d.antes_txt, false);
      decir('');
    }
    // La foto y el nombre llevan a la ficha con ese peso (sin peso, a la ficha a secas).
    var destino = d && d.url ? d.url : (pesos && pesos.base) || '';
    if (destino) {
      [].slice.call(tarjeta.querySelectorAll('[data-kcs-ir]')).forEach(function (a) { a.href = destino; });
    }
  }

  /* ---------------------------------------------------------------------------------------------
   * Volver con «Atrás».
   *
   * El navegador repone solo el formato, la cantidad y la altura de la página. El peso elegido de
   * un antiparasitario lo repone en su desplegable, pero sin avisar y tarde (Chrome, justo antes
   * de `load`): la tarjeta se quedaba con el precio y el «+» del peso de antes, y el «+» habría
   * añadido otro peso. Así que al cargar, y otra vez en `load`, se ajusta cada tarjeta a lo que
   * enseña su desplegable; y si el navegador no lo repuso, se recupera lo que se eligió aquí mismo
   * (se apunta en la pestaña, `sessionStorage`, al elegir).
   */
  var clavePesos = 'kcs-pesos:' + ref;
  var volviendo = (function () {
    try {
      var nav = performance.getEntriesByType('navigation')[0];
      return !!nav && nav.type === 'back_forward';
    } catch (e) { return false; }
  })();

  function pesosGuardados() {
    try { return JSON.parse(sessionStorage.getItem(clavePesos) || '{}') || {}; } catch (e) { return {}; }
  }

  function guardarPeso(tarjeta, ipa) {
    try {
      var todos = pesosGuardados();
      todos[(tarjeta.querySelector('[data-kcs-mas]').value || '').split('-')[0]] = ipa;
      sessionStorage.setItem(clavePesos, JSON.stringify(todos));
    } catch (e) { /* sin almacenamiento (modo privado), el navegador lo repone igual casi siempre */ }
  }

  function reponerPesos() {
    var guardados = volviendo ? pesosGuardados() : {};
    [].slice.call(caja.querySelectorAll('[data-kcs-peso]')).forEach(function (select) {
      var tarjeta = select.closest('.kcs__art');
      var boton = tarjeta && tarjeta.querySelector('[data-kcs-mas]');
      if (!boton || tarjeta.classList.contains('is-puesto')) {
        return;
      }
      var partes = (boton.value || '').split('-');
      var visto = parseInt(select.value, 10) || 0;
      var suyo = parseInt(guardados[partes[0]], 10) || 0;
      if (!visto && suyo && select.querySelector('option[value="' + suyo + '"]')) {
        visto = suyo;
        select.value = String(visto);
      }
      if (visto && visto !== (parseInt(partes[1], 10) || 0)) {
        ponerPeso(tarjeta, visto);
      }
      var cero = select.querySelector('option[value="0"]');
      if (visto && cero) {
        select.removeChild(cero);
      }
    });
  }

  /** Al cambiar de formato arriba, cada tarjeta por peso enseña los pesos que encajan con el nuevo. */
  function rehacerPesos(tarjeta, ipaPrincipal) {
    var pesos = pesosDe(tarjeta);
    var select = tarjeta.querySelector('[data-kcs-peso]');
    if (!pesos || !select || tarjeta.classList.contains('is-puesto')) {
      return;
    }
    var m = pesos.mapa[ipaPrincipal] || pesos.mapa[Object.keys(pesos.mapa)[0]];
    if (!m) {
      return;
    }
    // Si con el formato nuevo no encaja ningún peso, la tarjeta se aparta y entra la siguiente.
    if (m.opciones.length) {
      tarjeta.removeAttribute('data-kcs-oculto');
    } else {
      tarjeta.setAttribute('data-kcs-oculto', '');
    }
    select.textContent = '';
    if (!m.elegido) {
      var vacio = select.getAttribute('data-kcs-por') === 'talla' ? T.elige_opcion_talla : T.elige_opcion;
      select.appendChild(new Option(vacio || '', '0', true, true));
    }
    m.opciones.forEach(function (ipa) {
      select.appendChild(new Option(pesos.datos[ipa].etiqueta, String(ipa), false, ipa === m.elegido));
    });
    ponerPeso(tarjeta, m.elegido);
    if (!m.elegido && m.opciones.length) {
      var barato = m.opciones.reduce(function (a, b) { return pesos.datos[b].precio < pesos.datos[a].precio ? b : a; });
      pintarPrecio(tarjeta, pesos.datos[barato].precio_txt, '', true);
    }
  }

  /** Se ven las primeras que encajan, hasta el límite; las demás esperan ocultas. */
  var limite = parseInt(caja.getAttribute('data-kcs-limite'), 10) || 3;
  function mostrar() {
    var vistas = 0;
    [].slice.call(caja.querySelectorAll('.kcs__art')).forEach(function (t) {
      var cabe = !t.hasAttribute('data-kcs-oculto') && vistas < limite;
      t.hidden = !cabe;
      if (cabe) {
        vistas += 1;
      }
    });
  }

  caja.addEventListener('change', function (e) {
    var select = e.target;
    if (select && select.hasAttribute && select.hasAttribute('data-kcs-peso')) {
      var tarjeta = select.closest('.kcs__art');
      ponerPeso(tarjeta, parseInt(select.value, 10) || 0);
      guardarPeso(tarjeta, parseInt(select.value, 10) || 0);
      var cero = select.querySelector('option[value="0"]');
      if (cero && select.value !== '0') {
        select.removeChild(cero);
      }
    }
  });

  reponerPesos();
  // El navegador repone los desplegables al volver justo antes de `load`, cuando esto ya ha
  // corrido: se repasa entonces otra vez (y al volver de la caché de páginas, por si acaso).
  if (document.readyState !== 'complete') {
    window.addEventListener('load', reponerPesos);
  }
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
      reponerPesos();
    }
  });

  try {
    if (window.prestashop && typeof window.prestashop.on === 'function') {
      window.prestashop.on('updatedProduct', function (e) {
        var ipa = e && parseInt(e.id_product_attribute, 10);
        if (!ipa) {
          var campo = document.querySelector('input[name="id_product_attribute"], #idCombination');
          ipa = campo ? parseInt(campo.value, 10) : 0;
        }
        if (ipa > 0) {
          [].slice.call(caja.querySelectorAll('[data-kcs-pesos]')).forEach(function (t) { rehacerPesos(t, ipa); });
          mostrar();
        }
      });
    }
  } catch (e) { /* sin avisos, las tarjetas se quedan con los pesos del formato de entrada */ }

  function anadir(boton) {
    var tarjeta = boton.closest('.kcs__art');
    if (!url || !tarjeta || boton.disabled || tarjeta.classList.contains('is-puesto')) {
      return;
    }
    var partes = (boton.value || '').split('-');
    // Por peso y sin peso elegido: no se añade nada a ciegas, se pide el peso.
    if (!(parseInt(partes[1], 10) > 0)) {
      var select = tarjeta.querySelector('[data-kcs-peso]');
      tarjeta.classList.add('is-falta');
      decir(select && select.getAttribute('data-kcs-por') === 'talla' ? T.elige_talla : T.elige, true);
      if (select) {
        select.focus();
      }
      return;
    }
    boton.disabled = true;
    boton.setAttribute('aria-busy', 'true');
    tarjeta.classList.add('is-cargando');
    decir('');

    var datos = new FormData();
    // La ficha desde la que se añade, para poder contar qué pareja funciona.
    datos.append('ref', ref);
    datos.append('articulos[]', boton.value);

    fetch(url, { method: 'POST', body: datos, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function (d) {
        tarjeta.classList.remove('is-cargando');
        boton.removeAttribute('aria-busy');
        if (!d || !d.ok) {
          boton.disabled = false;
          decir((d && d.mensaje) || T.error, true);
          return;
        }
        tarjeta.classList.add('is-puesto');
        boton.setAttribute('aria-pressed', 'true');
        boton.setAttribute('aria-label', (T.en_cesta || '').split('%producto%').join(boton.getAttribute('data-kcs-nombre') || ''));
        decir(T.puesto || d.mensaje);
        avisarCesta(parseInt(partes[0], 10) || 0, parseInt(partes[1], 10) || 0, d);
      })
      .catch(function () {
        tarjeta.classList.remove('is-cargando');
        boton.removeAttribute('aria-busy');
        boton.disabled = false;
        decir(T.error, true);
      });
  }

  caja.addEventListener('click', function (e) {
    var boton = e.target.closest ? e.target.closest('[data-kcs-mas]') : null;
    if (boton && caja.contains(boton)) {
      e.preventDefault();
      anadir(boton);
    }
  });
})();
