/**
 * Kompy · La cesta de un solo almacén (kpycrosssell 1.7.0)
 *
 * Sin JavaScript todo funciona (la (i) y «Ver más» son <details>; «Cambiar» y «Dejarlo como está» son
 * formularios que vuelven a la cesta). Con JavaScript, el cambio se hace sin recargar: se manda al
 * controlador, se avisa a la tienda (`updateCart`) para que vuelva a pintar la lista y los totales (los
 * bloques se pintan de nuevo en el servidor), y el aviso del pie se pone al día. En la ventana «Añadido
 * a tu cesta», el producto de la ventana pasa a ser el nuevo y el bloque desaparece.
 *
 * Sin dependencias: si algo falla, la cesta sigue funcionando como sin JavaScript.
 */
(function () {
  'use strict';

  var pendiente = '';

  function textos(sec) {
    var n = sec ? sec.querySelector('[data-kcs-al-textos]') : null;
    try { return n ? JSON.parse(n.textContent) : {}; } catch (e) { return {}; }
  }

  function emitir(nombre, datos) {
    try {
      if (window.prestashop && typeof window.prestashop.emit === 'function') {
        window.prestashop.emit(nombre, datos);
      }
    } catch (e) { /* la cesta se ve al recargar */ }
  }

  function decir(sec, mensaje, mal) {
    var a = sec ? sec.querySelector('[data-kcs-al-aviso]') : null;
    if (!a) {
      return;
    }
    a.textContent = mensaje || '';
    a.classList.toggle('is-mal', !!mal);
  }

  function enviar(url, cuerpo) {
    cuerpo.append('ajax', '1');
    return fetch(url, { method: 'POST', body: cuerpo, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      });
  }

  /** La dirección del controlador y el token, de cualquier bloque que haya en la página. */
  function config() {
    var sec = document.querySelector('[data-kcs-al-url]');
    var cfg = document.querySelector('[data-kcs-al-config]');
    var c = {};
    try { c = cfg ? JSON.parse(cfg.textContent) : {}; } catch (e) { c = {}; }
    return {
      url: (sec && sec.getAttribute('data-kcs-al-url')) || c.url || '',
      token: (sec && sec.getAttribute('data-kcs-al-token')) || c.token || ''
    };
  }

  /* ---------------------------------------------------------------------------------------------
   * «Cambiar»: la alternativa entra, la línea sale, y la tienda repinta la cesta.
   */
  function cambiar(form) {
    var sec = form.closest('[data-kcs-al]');
    var boton = form.querySelector('button[type="submit"]');
    if (!sec || !boton || boton.disabled) {
      return;
    }
    var t = textos(sec);
    sec.classList.add('is-cambiando');
    boton.disabled = true;
    boton.setAttribute('aria-busy', 'true');
    decir(sec, t.cambiando || '');

    enviar(form.getAttribute('action'), new FormData(form))
      .then(function (d) {
        if (!d || !d.ok) {
          sec.classList.remove('is-cambiando');
          boton.disabled = false;
          boton.removeAttribute('aria-busy');
          decir(sec, (d && d.mensaje) || t.error, true);
          return;
        }
        var partes = ((form.querySelector('[name="a"]') || {}).value || '').split('-');
        // Para las analíticas (GTM lo recoge si quiere): qué salió, qué entró y desde dónde.
        try {
          window.dataLayer = window.dataLayer || [];
          window.dataLayer.push({
            event: 'kpy_cesta_cambiar',
            kpy_de: ((form.querySelector('[name="de"]') || {}).value || ''),
            kpy_a: partes.join('-'),
            kpy_origen: sec.getAttribute('data-kcs-al-origen') || 'linea'
          });
        } catch (e) { /* sin analíticas, igual */ }
        if (sec.getAttribute('data-kcs-al-origen') === 'ventana') {
          ventanaCambiada(sec, d);
        } else {
          // Lo que se dirá cuando la tienda haya repintado la lista (el bloque se va con ella).
          pendiente = d.multi ? (t.sigue || d.mensaje) : (t.cambiado || d.mensaje);
        }
        emitir('updateCart', {
          reason: { idProduct: parseInt(partes[0], 10) || 0, idProductAttribute: parseInt(partes[1], 10) || 0, linkAction: 'kcs-cambiar' },
          resp: d
        });
      })
      .catch(function () {
        sec.classList.remove('is-cambiando');
        boton.disabled = false;
        boton.removeAttribute('aria-busy');
        decir(sec, t.error, true);
      });
  }

  /** En la ventana «Añadido a tu cesta»: el producto de la ventana pasa a ser el nuevo. */
  function ventanaCambiada(sec, d) {
    var ventana = sec.closest('[data-kcs-al-ventana]');
    var modal = sec.closest('.blockcart-modal, .modal');
    if (modal) {
      var nombre = modal.querySelector('.blockcart-modal__name');
      var precio = modal.querySelector('.blockcart-modal__price');
      if (nombre && d.nombre) {
        nombre.textContent = d.nombre;
      }
      if (precio && d.precio_txt) {
        precio.textContent = d.precio_txt;
      }
      var propiedades = modal.querySelectorAll('.blockcart-modal__property');
      for (var i = 0; i < propiedades.length; i++) {
        propiedades[i].hidden = true;
      }
      if (d.formato && propiedades.length) {
        propiedades[0].hidden = false;
        propiedades[0].textContent = d.formato;
      }
    }
    if (ventana) {
      var estado = ventana.querySelector('.kcs-al-estado');
      var hecho = ventana.querySelector('[data-kcs-al-hecho]');
      if (estado) {
        estado.hidden = true;
      }
      sec.hidden = true;
      if (hecho) {
        hecho.hidden = false;
      }
    }
  }

  /* ---------------------------------------------------------------------------------------------
   * «Dejarlo como está»: el bloque se va; la etiqueta de la línea se queda.
   */
  function dejar(form) {
    var sec = form.closest('[data-kcs-al]');
    try {
      enviar(form.getAttribute('action'), new FormData(form)).catch(function () { /* se apunta otra vez al recargar */ });
    } catch (e) { /* igual */ }
    if (sec) {
      sec.hidden = true;
    }
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.matches) {
      return;
    }
    if (form.matches('[data-kcs-al-cambiar]')) {
      e.preventDefault();
      cambiar(form);
    } else if (form.matches('[data-kcs-al-dejar]')) {
      e.preventDefault();
      dejar(form);
    }
  });

  /* ---------------------------------------------------------------------------------------------
   * Contar «Ver más» y la (i), para saber qué se usa. Sin esperar respuesta.
   */
  document.addEventListener('toggle', function (e) {
    var d = e.target;
    if (!d || !d.open || !d.hasAttribute) {
      return;
    }
    var evento = d.hasAttribute('data-kcs-al-mas') ? 'vermas' : (d.hasAttribute('data-kcs-al-info') ? 'info' : '');
    if (!evento) {
      return;
    }
    var c = config();
    var clave = d.getAttribute('data-kcs-al-info') || ((d.closest('[data-kcs-al]') || {}).getAttribute ? d.closest('[data-kcs-al]').getAttribute('data-kcs-al') : '');
    if (!c.url || !c.token) {
      return;
    }
    var cuerpo = new FormData();
    cuerpo.append('token', c.token);
    cuerpo.append('accion', 'contar');
    cuerpo.append('evento', evento);
    cuerpo.append('de', clave || '');
    cuerpo.append('ajax', '1');
    try {
      fetch(c.url, { method: 'POST', body: cuerpo, credentials: 'same-origin', keepalive: true }).catch(function () {});
    } catch (err) { /* contar no es tan importante como vender */ }
  }, true);

  /* ---------------------------------------------------------------------------------------------
   * El aviso del pie: se coloca justo debajo de la lista y se pone al día cada vez que la tienda
   * repinta la cesta (cantidades, borrados, cambios): se le pide al servidor su estado.
   */
  function colocarResumen() {
    var resumen = document.querySelector('[data-kcs-al-resumen]');
    var lista = document.querySelector('.cart__overview, .cart-overview');
    if (resumen && lista && lista.parentNode && resumen.previousElementSibling !== lista) {
      lista.parentNode.insertBefore(resumen, lista.nextSibling);
    }
  }

  function ponerAlDia() {
    var c = config();
    if (!c.url || !document.querySelector('.cart__overview, .cart-overview')) {
      return;
    }
    fetch(c.url + (c.url.indexOf('?') >= 0 ? '&' : '?') + 'accion=estado&ajax=1', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) {
          return;
        }
        var actual = document.querySelector('[data-kcs-al-resumen]');
        if (d.html) {
          var caja = document.createElement('div');
          caja.innerHTML = d.html;
          var nuevo = caja.firstElementChild;
          if (actual && actual.parentNode) {
            actual.parentNode.replaceChild(nuevo, actual);
          } else {
            var lista = document.querySelector('.cart__overview, .cart-overview');
            if (lista && lista.parentNode) {
              lista.parentNode.insertBefore(nuevo, lista.nextSibling);
            }
          }
        } else if (actual && actual.parentNode) {
          actual.parentNode.removeChild(actual);
        }
        // Lo que se dice tras un cambio, en el hueco de avisos de la cesta del tema.
        if (pendiente) {
          var hueco = document.querySelector('.js-cart-update-alert');
          if (hueco) {
            hueco.textContent = pendiente;
            hueco.className = 'js-cart-update-alert alert ' + (d.multi ? 'alert-warning' : 'alert-success');
            hueco.setAttribute('role', 'status');
          }
          pendiente = '';
        }
      })
      .catch(function () { /* el aviso se pone al día al recargar */ });
  }

  colocarResumen();
  try {
    if (window.prestashop && typeof window.prestashop.on === 'function') {
      window.prestashop.on('updatedCart', function () { setTimeout(ponerAlDia, 50); });
    }
  } catch (e) { /* sin avisos, el pie se pone al día al recargar */ }
})();
