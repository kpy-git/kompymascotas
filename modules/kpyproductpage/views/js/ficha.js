/**
 * Kompy · Ficha de producto (kpyproductpage). Sin librerías; se carga diferido.
 *
 * 1. En PC (desde 992 px) todas las secciones abiertas: en móvil son desplegables.
 * 2. Calculadora de la ración diaria: al elegir peso (o tabla, o columna) cambia la ración y
 *    cuánto dura cada formato. Sin JavaScript se ve la ración de la fila del medio y la tabla.
 */
(function () {
  'use strict';

  var pc = window.matchMedia('(min-width: 992px)');
  function abrirEnPC() {
    if (!pc.matches) {
      return;
    }
    var todas = document.querySelectorAll('.kpp details[data-kpp-acc]');
    for (var i = 0; i < todas.length; i++) {
      todas[i].open = true;
    }
  }
  abrirEnPC();
  if (pc.addEventListener) {
    pc.addEventListener('change', abrirEnPC);
  }

  // Un enlace a una sección plegada («Mostrar descripción completa») la abre antes de bajar.
  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[href^="#"]') : null;
    var destino = a && a.getAttribute('href').length > 1 ? document.getElementById(a.getAttribute('href').slice(1)) : null;
    var plegable = destino && destino.closest ? destino.closest('.kpp details[data-kpp-acc]') : null;
    if (plegable) {
      plegable.open = true;
    }
  });

  var calcs = document.querySelectorAll('[data-kpp-calc]');
  for (var c = 0; c < calcs.length; c++) {
    iniciar(calcs[c]);
  }

  function iniciar(raiz) {
    var datosEl = raiz.querySelector('[data-kpp-datos]');
    var datos;
    try {
      datos = JSON.parse(datosEl.textContent);
    } catch (e) {
      return;
    }
    if (!datos || !datos.tablas || !datos.tablas.length) {
      return;
    }
    var filasPrimera = datos.tablas[0].filas.length;
    var estado = { tabla: 0, fila: Math.floor((filasPrimera - 1) / 2), col: 0 };
    var cajaFilas = raiz.querySelector('[data-kpp-filas]');
    var cajaCols = raiz.querySelector('[data-kpp-columnas]');

    raiz.addEventListener('click', function (ev) {
      var b = ev.target.closest('button');
      if (!b || !raiz.contains(b)) {
        return;
      }
      if (b.hasAttribute('data-kpp-tabla')) {
        estado.tabla = +b.getAttribute('data-kpp-tabla');
        var n = datos.tablas[estado.tabla].filas.length;
        estado.fila = Math.min(estado.fila, n - 1);
        estado.col = 0;
        pintarBotones();
      } else if (b.hasAttribute('data-kpp-fila')) {
        estado.fila = +b.getAttribute('data-kpp-fila');
      } else if (b.hasAttribute('data-kpp-col')) {
        estado.col = +b.getAttribute('data-kpp-col');
      } else {
        return;
      }
      pintar();
    });

    // Al cambiar de tabla, los botones de peso y de columna son otros.
    function pintarBotones() {
      var t = datos.tablas[estado.tabla];
      raiz.querySelector('[data-kpp-titulo]').textContent = t.titulo;
      var detalle = raiz.querySelector('[data-kpp-detalle]');
      if (detalle) {
        detalle.textContent = t.detalle || '';
      }
      cajaFilas.textContent = '';
      t.filas.forEach(function (f, i) {
        cajaFilas.appendChild(boton('data-kpp-fila', i, f.etiqueta));
      });
      if (cajaCols) {
        cajaCols.textContent = '';
        if (t.columnas.length > 1) {
          t.columnas.forEach(function (col, i) {
            cajaCols.appendChild(boton('data-kpp-col', i, col));
          });
        }
      }
    }

    function boton(atributo, i, texto) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'kpp-chip';
      b.setAttribute(atributo, i);
      b.setAttribute('aria-pressed', 'false');
      b.textContent = texto;
      return b;
    }

    function pintar() {
      var t = datos.tablas[estado.tabla];
      var fila = t.filas[estado.fila];
      marcar('[data-kpp-tabla]', 'data-kpp-tabla', estado.tabla);
      marcar('[data-kpp-fila]', 'data-kpp-fila', estado.fila);
      marcar('[data-kpp-col]', 'data-kpp-col', estado.col);
      raiz.querySelector('[data-kpp-valor]').textContent = fila.valores[estado.col] || '';

      var lista = raiz.querySelector('[data-kpp-dias]');
      lista.textContent = '';
      var cant = fila.cantidades[estado.col];
      datos.formatos.forEach(function (f) {
        var d = dias(cant, f);
        if (d >= 1) {
          var li = document.createElement('li');
          li.textContent = f.corto + ': ' + d + (d === 1 ? ' día' : ' días');
          lista.appendChild(li);
        }
      });
    }

    function marcar(selector, atributo, valor) {
      var bs = raiz.querySelectorAll(selector);
      for (var i = 0; i < bs.length; i++) {
        bs[i].setAttribute('aria-pressed', +bs[i].getAttribute(atributo) === valor ? 'true' : 'false');
      }
    }
  }

  // Igual que KppRacion::dias() en PHP.
  function dias(cant, f) {
    if (!cant || cant.n <= 0) {
      return 0;
    }
    if (cant.tipo === 'unidades' && f.unidades) {
      return Math.floor(f.unidades / cant.n);
    }
    if (cant.tipo === 'gramos' && f.gramos) {
      return Math.floor(f.gramos / cant.n);
    }
    // En la mixta («1 sobre + 20 g»), el pienso dice cuánto le dura con los gramos.
    return cant.alt ? dias(cant.alt, f) : 0;
  }
})();
