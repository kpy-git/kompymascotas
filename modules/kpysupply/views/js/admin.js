/*
 * Kompy · Acuerdos con proveedores (back office). Lo poco que hace falta en la pantalla: el
 * formulario de subir una lista (las fechas siguen a la promoción elegida, «hasta nueva orden»
 * apaga la fecha de fin, el % general solo para los tipos con %), copiar las filas no encontradas,
 * y bajar hasta la comprobación o el detalle cuando la página vuelve con uno.
 */
(function () {
  'use strict';

  function listo(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  listo(function () {
    var form = document.querySelector('.kps-subir');
    if (form) {
      var tipos = form.querySelectorAll('input[name="kps_tipo"]');
      var valor = form.querySelector('.kps-valor-general');
      var valorPista = form.querySelector('.kps-valor-pista');
      var promo = form.querySelector('select[name="kps_promo"]');
      var desde = form.querySelector('input[name="kps_desde"]');
      var hasta = form.querySelector('input[name="kps_hasta"]');
      var sinFin = form.querySelector('input[name="kps_sin_fin"]');
      var fechasNota = form.querySelector('.kps-fechas-nota');

      function tipoElegido() {
        for (var i = 0; i < tipos.length; i++) {
          if (tipos[i].checked) {
            return tipos[i].value;
          }
        }
        return '';
      }

      function pintarTipo() {
        var t = tipoElegido();
        if (valor) {
          valor.hidden = t === 'coste';
        }
        if (valorPista) {
          valorPista.textContent = t === 'coste'
            ? 'El precio va en la lista, al lado de cada EAN (sin IVA, por unidad de venta).'
            : (t === 'redencion' ? 'El % de las ventas sin IVA que paga el proveedor. Si cada fila trae el suyo, manda el de la fila.' : 'El % sobre el precio de coste neto. Si cada fila trae el suyo, manda el de la fila.');
        }
      }

      function pintarFechas() {
        var conPromo = promo && promo.value !== '0' && promo.value !== '';
        var opcion = conPromo ? promo.options[promo.selectedIndex] : null;
        if (conPromo && opcion) {
          if (opcion.getAttribute('data-desde')) {
            desde.value = opcion.getAttribute('data-desde');
          }
          var h = opcion.getAttribute('data-hasta') || '';
          sinFin.checked = h === '';
          hasta.value = h;
        }
        desde.disabled = !!conPromo;
        sinFin.disabled = !!conPromo;
        hasta.disabled = !!conPromo || sinFin.checked;
        if (fechasNota) {
          fechasNota.textContent = conPromo
            ? 'Las fechas son las de la promoción elegida; si cambian allí, el acuerdo las sigue.'
            : 'El acuerdo vale desde las 0:00 del primer día hasta las 23:59 del último.';
        }
      }

      for (var i = 0; i < tipos.length; i++) {
        tipos[i].addEventListener('change', pintarTipo);
      }
      if (promo) {
        promo.addEventListener('change', pintarFechas);
      }
      if (sinFin) {
        sinFin.addEventListener('change', pintarFechas);
      }
      pintarTipo();
      pintarFechas();

      // Los campos apagados no viajan con el formulario: se encienden justo antes de mandarlo.
      form.addEventListener('submit', function () {
        desde.disabled = false;
        hasta.disabled = false;
        sinFin.disabled = false;
      });
    }

    // Copiar una lista (las filas no encontradas, para reclamarlas al proveedor).
    var copiar = document.querySelectorAll('[data-kps-copiar]');
    for (var c = 0; c < copiar.length; c++) {
      copiar[c].addEventListener('click', function (e) {
        e.preventDefault();
        var boton = this;
        var origen = document.getElementById(boton.getAttribute('data-kps-copiar'));
        if (!origen) {
          return;
        }
        var texto = origen.value !== undefined ? origen.value : origen.textContent;
        var hecho = function () {
          var antes = boton.textContent;
          boton.textContent = 'Copiado';
          setTimeout(function () { boton.textContent = antes; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(texto).then(hecho, function () { origen.select && origen.select(); });
        } else if (origen.select) {
          origen.select();
          try { document.execCommand('copy'); hecho(); } catch (err) { /* nada */ }
        }
      });
    }

    // Al volver con una comprobación o un detalle, bajar hasta él.
    var destino = document.getElementById('comprobacion') || (location.hash ? document.querySelector(location.hash) : null);
    if (destino && destino.scrollIntoView) {
      setTimeout(function () { destino.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 50);
    }

    // Borrar un acuerdo entero: el botón solo se enciende con «BORRAR» escrito.
    var confirmar = document.querySelectorAll('input[name="confirmar"]');
    for (var k = 0; k < confirmar.length; k++) {
      (function (campo) {
        var boton = campo.form ? campo.form.querySelector('button[name="kpsBorrarLote"]') : null;
        if (!boton) {
          return;
        }
        var pintar = function () { boton.disabled = campo.value.trim() !== 'BORRAR'; };
        campo.addEventListener('input', pintar);
        pintar();
      })(confirmar[k]);
    }
  });
})();
