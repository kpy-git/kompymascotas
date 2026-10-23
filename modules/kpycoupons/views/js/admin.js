/**
 * Kompy · Promociones (back office). Lo que hace fácil el formulario, sin dependencias:
 *
 *   - marcas y categorías se buscan escribiendo y se eligen como fichas;
 *   - al elegir una marca salen sus gamas, sus tamaños y sus packs, para afinar con un clic;
 *   - sugiere el código (BOSKE10) y el título de Google mientras no se escriban a mano;
 *   - atajos de duración (1 semana, 15 días, 1 mes, fin de mes);
 *   - el enlace que deja puesto el cupón, al día con el código;
 *   - una frase abajo que dice qué se va a guardar, para leerlo antes de darle;
 *   - en los productos de la promoción: buscar, quitar y volver a meter;
 *   - en un regalo: el buscador de artículos (el regalo para todos, el de reserva) y «Cambiar
 *     regalo» por fila, que lo apunta en `regalo_parejas`;
 *   - avisa si se sale con cambios sin guardar.
 *
 * Lo que se ve según el tipo de promoción lo hace el CSS (`:has()`), no esto. PrestaShop carga este
 * fichero en la cabecera, antes que el formulario: por eso se arranca cuando la página está lista.
 */
(function () {
  'use strict';

  function arrancar() {
    var raiz = document.getElementById('kpc-admin');
    if (!raiz) {
      return;
    }
    try {
      iniciar(raiz);
    } catch (e) {
      // Que no se quede callado: sin esto, un fallo deja la pantalla a medias sin decir nada.
      var aviso = document.createElement('div');
      aviso.className = 'alert alert-danger';
      aviso.textContent = 'La pantalla no ha cargado del todo (' + (e && e.message ? e.message : e) + '). Recarga la página; si sigue, avisa a Julián.';
      raiz.insertBefore(aviso, raiz.firstChild);
      if (window.console) { window.console.error(e); }
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', arrancar);
  } else {
    arrancar();
  }

  function iniciar(raiz) {
    var form = document.getElementById('kpc-form');
    var datos = {};
    try {
      datos = JSON.parse(document.getElementById('kpc-datos').textContent) || {};
    } catch (e) {
      datos = {};
    }
    var marcas = datos.marcas || [];
    var detalle = datos.detalle || {};

    function $(sel, dentro) { return (dentro || raiz).querySelector(sel); }
    function $$(sel, dentro) { return [].slice.call((dentro || raiz).querySelectorAll(sel)); }
    function campo(nombre) { return form ? form.elements[nombre] : null; }
    function valor(nombre) {
      var c = campo(nombre);
      if (!c) { return ''; }
      if (c.length !== undefined && !c.tagName) {
        var marcado = [].slice.call(c).filter(function (x) { return x.checked; })[0];
        return marcado ? marcado.value : '';
      }
      return c.type === 'checkbox' ? (c.checked ? c.value : '') : c.value;
    }
    function normalizar(t) {
      return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim();
    }
    function pct(n) {
      var x = parseFloat(String(n).replace(',', '.'));
      return isNaN(x) ? '' : String(Math.round(x * 10) / 10).replace('.', ',');
    }
    /** Un elemento con clase y texto (el texto nunca como HTML). */
    function el(etiqueta, clase, texto) {
      var e = document.createElement(etiqueta);
      if (clase) { e.className = clase; }
      if (texto !== undefined && texto !== null) { e.textContent = texto; }
      return e;
    }

    /* ---------- Tipo (cupón, 2ª unidad, Special Price) y modo del Special Price ---------- */
    function tipo() { return valor('tipo'); }
    // El modo solo cuenta en un Special Price; en lo demás es siempre «el mismo %».
    function modo() { return tipo() === 'special_price' ? (valor('modo_precio') || 'pct') : 'pct'; }
    function placeholderLista() {
      var lista = campo('lista');
      if (!lista) { return; }
      if (!lista.hasAttribute('data-kpc-placeholder')) {
        lista.setAttribute('data-kpc-placeholder', lista.placeholder);
      }
      lista.placeholder = modo() !== 'pct' ? lista.getAttribute('data-kpc-placeholder-valor') : lista.getAttribute('data-kpc-placeholder');
    }

    /* ---------- Buscadores de marcas y categorías ---------- */
    function buscador(caja, fuente, alCambiar) {
      var nombre = caja.getAttribute('data-kpc-nombre');
      var chips = $('[data-kpc-chips]', caja);
      var buscar = $('[data-kpc-buscar]', caja);
      var opciones = $('[data-kpc-opciones]', caja);
      var porId = {};
      fuente.forEach(function (o) { porId[o.id] = o; o._t = normalizar(o.n + ' ' + (o.r || '')); o._n = normalizar(o.n); });
      var lista = (caja.getAttribute('data-kpc-valores') || '').split(',').filter(Boolean).map(Number);
      var activa = -1;
      var vistas = [];

      function pintarChips() {
        chips.innerHTML = '';
        lista.forEach(function (id) {
          var o = porId[id];
          var chip = el('span', 'kpc-ficha');
          chip.appendChild(el('span', 'kpc-ficha__texto', o ? o.n : '#' + id));
          var quitar = el('button', 'kpc-ficha__quitar', '×');
          quitar.type = 'button';
          quitar.setAttribute('aria-label', 'Quitar ' + (o ? o.n : id));
          quitar.addEventListener('click', function () {
            lista.splice(lista.indexOf(id), 1);
            pintarChips();
            alCambiar();
            buscar.focus();
          });
          chip.appendChild(quitar);
          var oculto = el('input');
          oculto.type = 'hidden';
          oculto.name = nombre;
          oculto.value = id;
          chip.appendChild(oculto);
          if (o && o.r) { chip.title = o.r + ' › ' + o.n; }
          chips.appendChild(chip);
        });
      }

      function filtrar() {
        var q = normalizar(buscar.value);
        if (!q) { cerrar(); return; }
        var palabras = q.split(' ');
        vistas = fuente.filter(function (o) {
          if (lista.indexOf(o.id) !== -1) { return false; }
          return palabras.every(function (p) { return o._t.indexOf(p) !== -1; });
        }).sort(function (a, b) {
          // Primero las que empiezan por lo escrito, luego las que lo tienen en el nombre; y las que
          // tienen más productos antes.
          var pa = a._n.indexOf(q) === 0 ? 0 : (a._n.indexOf(palabras[0]) !== -1 ? 1 : 2);
          var pb = b._n.indexOf(q) === 0 ? 0 : (b._n.indexOf(palabras[0]) !== -1 ? 1 : 2);
          return pa - pb || (b.c || 0) - (a.c || 0);
        }).slice(0, 40);
        opciones.innerHTML = '';
        if (!vistas.length) {
          opciones.appendChild(el('li', 'kpc-opcion kpc-opcion--nada', 'Nada con «' + buscar.value + '»'));
        }
        vistas.forEach(function (o, i) {
          var li = el('li', 'kpc-opcion');
          li.setAttribute('role', 'option');
          li.appendChild(el('span', 'kpc-opcion__nombre', o.n));
          li.appendChild(el('span', 'kpc-opcion__cuenta', o.c ? o.c + ' prod.' : ''));
          li.appendChild(el('span', 'kpc-opcion__ruta', o.r || ''));
          li.addEventListener('mousedown', function (e) { e.preventDefault(); elegir(i); });
          opciones.appendChild(li);
        });
        activa = vistas.length ? 0 : -1;
        marcar();
        opciones.hidden = false;
      }
      function marcar() {
        $$('.kpc-opcion', opciones).forEach(function (li, i) { li.classList.toggle('is-activa', i === activa); });
      }
      function elegir(i) {
        var o = vistas[i];
        if (!o) { return; }
        lista.push(o.id);
        buscar.value = '';
        cerrar();
        pintarChips();
        alCambiar();
      }
      function cerrar() { opciones.hidden = true; opciones.innerHTML = ''; vistas = []; activa = -1; }

      buscar.addEventListener('input', filtrar);
      buscar.addEventListener('focus', function () { if (buscar.value) { filtrar(); } });
      buscar.addEventListener('blur', function () { setTimeout(cerrar, 120); });
      buscar.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' && vistas.length) { e.preventDefault(); activa = (activa + 1) % vistas.length; marcar(); }
        else if (e.key === 'ArrowUp' && vistas.length) { e.preventDefault(); activa = (activa - 1 + vistas.length) % vistas.length; marcar(); }
        else if (e.key === 'Enter') { e.preventDefault(); if (activa >= 0) { elegir(activa); } }
        else if (e.key === 'Escape') { cerrar(); }
        else if (e.key === 'Backspace' && !buscar.value && lista.length) { lista.pop(); pintarChips(); alCambiar(); }
      });
      pintarChips();
      return {
        ids: function () { return lista.slice(); },
        nombres: function () { return lista.map(function (id) { return porId[id] ? porId[id].n : '#' + id; }); },
        quitar: function (ids) {
          var antes = lista.length;
          lista = lista.filter(function (id) { return ids.indexOf(id) === -1; });
          if (lista.length !== antes) { pintarChips(); }
        }
      };
    }

    var cajaMarcas = $('[data-kpc-picker="marcas"]');
    var porMarca = {};
    marcas.forEach(function (m) { porMarca[m.id] = m; });
    var buscadorMarcas = cajaMarcas ? buscador(cajaMarcas, marcas, function () { pintarAfinar(); cambio(); }) : null;

    /* ---------- Lo de las marcas elegidas: gamas, formatos y packs ---------- */
    var cajaAfinar = $('[data-kpc-afinar]');
    var cajaGamas = $('[data-kpc-gamas]');
    var cajaTamanos = $('[data-kpc-tamanos]');
    var cajaPacks = $('[data-kpc-packs]');

    // Lo guardado: las gamas marcadas en su árbol, los formatos y los packs elegidos.
    var gamasMarcadas = {};
    ((cajaGamas && cajaGamas.getAttribute('data-kpc-marcadas')) || '').split(',').filter(Boolean).forEach(function (id) { gamasMarcadas[id] = true; });
    var tamanosMarcados = {};
    try {
      (JSON.parse(($('#kpc-tamanos-elegidos') || {}).textContent || '[]') || []).forEach(function (t) { tamanosMarcados[t] = true; });
    } catch (e) { tamanosMarcados = {}; }
    var packsMarcados = {};
    ((cajaPacks && cajaPacks.getAttribute('data-kpc-marcados')) || '').split(',').filter(Boolean).forEach(function (n) { packsMarcados[n] = true; });

    function ficha(nombreCampo, valorCampo, texto, cuenta, marcada) {
      var l = el('label', 'kpc-ficha-check');
      var c = el('input');
      c.type = 'checkbox';
      c.name = nombreCampo;
      c.value = valorCampo;
      c.checked = !!marcada;
      l.appendChild(c);
      l.appendChild(el('span', '', texto));
      if (cuenta) { l.appendChild(el('small', '', String(cuenta))); }
      return l;
    }
    function ofrecidos(cual, lista) {
      var oculto = $('[data-kpc-ofrecidos="' + cual + '"]');
      if (oculto) { oculto.value = lista.join('|'); }
    }

    function pintarAfinar() {
      var elegidas = buscadorMarcas ? buscadorMarcas.ids() : [];
      if (cajaAfinar) { cajaAfinar.hidden = elegidas.length === 0; }

      // Gamas, una caja por marca.
      if (cajaGamas) {
        cajaGamas.innerHTML = '';
        elegidas.forEach(function (idMarca) {
          var m = porMarca[idMarca] || { n: '#' + idMarca, c: 0 };
          var g = (detalle[idMarca] || {}).g || [];
          var bloque = el('div', 'kpc-marca-bloque');
          var titulo = el('p', 'kpc-pregunta');
          titulo.appendChild(document.createTextNode('Gamas de '));
          titulo.appendChild(el('strong', '', m.n));
          titulo.appendChild(el('small', '', g.length ? ' — sin marcar ninguna, toda la marca (' + m.c + ' productos)' : ''));
          bloque.appendChild(titulo);
          if (!g.length) {
            bloque.appendChild(el('p', 'kpc-nota', m.n + ' no tiene gamas en la tienda: entra entera, o se afina con los tamaños, los packs o la lista.'));
            cajaGamas.appendChild(bloque);
            return;
          }
          var arbol = el('div', 'kpc-arbol');
          if (g.length > 12) {
            var filtro = el('input', 'form-control kpc-arbol__buscar');
            filtro.type = 'search';
            filtro.placeholder = 'Buscar una gama de ' + m.n + '…';
            filtro.setAttribute('aria-label', filtro.placeholder);
            filtro.addEventListener('input', function () {
              var q = normalizar(filtro.value);
              $$('.kpc-rama', arbol).forEach(function (r) { r.hidden = q !== '' && r._texto.indexOf(q) === -1; });
            });
            // Buscar no es un cambio de la promoción.
            filtro.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
            bloque.appendChild(filtro);
          }
          g.forEach(function (n) {
            var rama = ficha('categorias[]', n[0], n[2], n[3], gamasMarcadas[n[0]]);
            rama.className = 'kpc-rama';
            rama.style.setProperty('--sangria', n[1]);
            rama._texto = normalizar(n[2]);
            rama.querySelector('input').addEventListener('change', function (e) {
              if (e.target.checked) { gamasMarcadas[n[0]] = true; } else { delete gamasMarcadas[n[0]]; }
            });
            arbol.appendChild(rama);
          });
          bloque.appendChild(arbol);
          bloque.appendChild(el('small', 'kpc-nota', 'Una gama lleva todo lo que cuelga de ella.'));
          cajaGamas.appendChild(bloque);
        });
      }

      // Tamaños: los de todas las marcas elegidas, por envase; y los que ya estaban elegidos.
      if (cajaTamanos) {
        var porTam = {};
        var orden = [];
        elegidas.forEach(function (idMarca) {
          ((detalle[idMarca] || {}).t || []).forEach(function (t) {
            if (!porTam[t[0]]) { porTam[t[0]] = { grupo: t[1], corto: t[2], n: 0 }; orden.push(t[0]); }
            porTam[t[0]].n += t[3];
          });
        });
        Object.keys(tamanosMarcados).forEach(function (t) {
          if (!porTam[t]) { porTam[t] = { grupo: 'Elegidos antes', corto: t, n: 0 }; orden.push(t); }
        });
        cajaTamanos.innerHTML = '';
        var grupos = {};
        var ordenGrupos = [];
        orden.forEach(function (t) {
          var gr = porTam[t].grupo;
          if (!grupos[gr]) {
            grupos[gr] = el('div', 'kpc-grupo');
            grupos[gr].appendChild(el('span', 'kpc-grupo__nombre', gr));
            ordenGrupos.push(gr);
          }
          var f = ficha('tamanos[]', t, porTam[t].corto, porTam[t].n, tamanosMarcados[t]);
          f.querySelector('input').addEventListener('change', function (e) {
            if (e.target.checked) { tamanosMarcados[t] = true; } else { delete tamanosMarcados[t]; }
          });
          grupos[gr].appendChild(f);
        });
        ordenGrupos.forEach(function (gr) { cajaTamanos.appendChild(grupos[gr]); });
        var cajaT = $('[data-kpc-caja-tamanos]');
        if (cajaT) { cajaT.hidden = orden.length === 0; }
        ofrecidos('tamanos', orden);
      }

      // «¿Solo algunos packs?»: los packs que tienen las marcas elegidas (de 2, de 4…). Sin marcar
      // ninguno, entran los sueltos y todos sus packs; marcando, solo esos packs (Schesir, packs de 2).
      if (cajaPacks) {
        var niveles = {};
        elegidas.forEach(function (idMarca) {
          ((detalle[idMarca] || {}).p || []).forEach(function (p) {
            if (p[0] > 1) { niveles[p[0]] = (niveles[p[0]] || 0) + p[1]; }
          });
        });
        Object.keys(packsMarcados).forEach(function (n) { if (!(n in niveles)) { niveles[n] = 0; } });
        cajaPacks.innerHTML = '';
        Object.keys(niveles).map(Number).sort(function (a, b) { return a - b; }).forEach(function (n) {
          var nodo = ficha('packs[]', String(n), 'Pack de ' + n, niveles[n], packsMarcados[n]);
          nodo.querySelector('input').addEventListener('change', function (e) {
            if (e.target.checked) { packsMarcados[n] = true; } else { delete packsMarcados[n]; }
          });
          cajaPacks.appendChild(nodo);
        });
        var cajaP = $('[data-kpc-caja-packs]');
        if (cajaP) { cajaP.hidden = Object.keys(niveles).length === 0; }
      }
    }
    pintarAfinar();

    // Con un % o un precio por artículo, los productos salen siempre de la lista.
    function segunModo() {
      if (tipo() === 'special_price' && modo() !== 'pct' && valor('elegir') === 'marca') {
        var porLista = form && form.querySelector('input[name="elegir"][value="lista"]');
        if (porLista) { porLista.checked = true; }
      }
    }

    /* ---------- El regalo: buscador de artículos (el mismo para todos, la reserva, una fila) ---------- */
    var urlBuscar = raiz.getAttribute('data-kpc-buscar') || '';
    function euros(n) {
      try { return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n); } catch (e) { return String(n).replace('.', ',') + ' €'; }
    }
    function textoDe(a) {
      var n = a.nombre.length > 48 ? a.nombre.slice(0, 46).replace(/\s+$/, '') + '…' : a.nombre;
      return a.formato ? n + ' · ' + a.formato : n;
    }
    function detalleDe(a) {
      return [a.marca, euros(a.precio), 'stock ' + a.stock, a.ean ? 'EAN ' + a.ean : ''].filter(Boolean).join(' · ');
    }
    /**
     * Un buscador de artículos: se escribe, la pantalla contesta sin recargar (`&action=buscar`), se
     * elige, y queda en el campo escondido («producto-formato») y en una ficha con su «×».
     * `alElegir(articulo | null)` avisa de lo elegido (o de que se ha quitado).
     */
    function buscadorArticulo(caja, alElegir) {
      var valor = $('[data-kpc-articulo-valor]', caja);
      var elegido = $('[data-kpc-articulo-elegido]', caja);
      var texto = $('[data-kpc-articulo-texto]', caja);
      var detalle = $('[data-kpc-articulo-detalle]', caja);
      var buscar = $('[data-kpc-articulo-buscar]', caja);
      var opciones = $('[data-kpc-opciones]', caja);
      var quitar = $('[data-kpc-articulo-quitar]', caja);
      if (!valor || !buscar || !opciones) { return; }
      var vistas = [];
      var activa = -1;
      var espera = 0;
      var peticion = 0;
      function cerrar() { opciones.hidden = true; opciones.innerHTML = ''; vistas = []; activa = -1; }
      function marcar() { $$('.kpc-opcion', opciones).forEach(function (li, i) { li.classList.toggle('is-activa', i === activa); }); }
      function pintar(lista, q) {
        opciones.innerHTML = '';
        vistas = lista;
        if (!lista.length) { opciones.appendChild(el('li', 'kpc-opcion kpc-opcion--nada', 'Nada a la venta con «' + q + '»')); }
        lista.forEach(function (a, i) {
          var li = el('li', 'kpc-opcion');
          li.setAttribute('role', 'option');
          li.appendChild(el('span', 'kpc-opcion__nombre', textoDe(a)));
          li.appendChild(el('span', 'kpc-opcion__cuenta', euros(a.precio)));
          li.appendChild(el('span', 'kpc-opcion__detalle', [a.marca, 'stock ' + a.stock, a.ean ? 'EAN ' + a.ean : '', a.valor].filter(Boolean).join(' · ')));
          li.addEventListener('mousedown', function (e) { e.preventDefault(); elegir(i); });
          opciones.appendChild(li);
        });
        activa = lista.length ? 0 : -1;
        marcar();
        opciones.hidden = false;
      }
      function pedir() {
        var q = buscar.value.trim();
        if (q.length < 2 || !urlBuscar || !window.fetch) { cerrar(); return; }
        var mia = ++peticion;
        fetch(urlBuscar + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (lista) { if (mia === peticion) { pintar(Array.isArray(lista) ? lista : [], q); } })
          .catch(function () { if (mia === peticion) { pintar([], q); } });
      }
      function elegir(i) {
        var a = vistas[i];
        if (!a) { return; }
        valor.value = a.valor;
        if (texto) { texto.textContent = textoDe(a); }
        if (detalle) { detalle.textContent = detalleDe(a); }
        if (elegido) { elegido.hidden = false; elegido.classList.remove('is-mal'); }
        buscar.value = '';
        buscar.hidden = true;
        cerrar();
        valor.dispatchEvent(new Event('change', { bubbles: true }));
        if (alElegir) { alElegir(a); }
      }
      buscar.addEventListener('input', function (e) {
        // Buscar no es un cambio de la promoción.
        e.stopPropagation();
        clearTimeout(espera);
        espera = setTimeout(pedir, 220);
      });
      buscar.addEventListener('focus', function () { if (buscar.value.trim().length >= 2) { pedir(); } });
      buscar.addEventListener('blur', function () { setTimeout(cerrar, 150); });
      buscar.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' && vistas.length) { e.preventDefault(); activa = (activa + 1) % vistas.length; marcar(); }
        else if (e.key === 'ArrowUp' && vistas.length) { e.preventDefault(); activa = (activa - 1 + vistas.length) % vistas.length; marcar(); }
        else if (e.key === 'Enter') { e.preventDefault(); if (activa >= 0) { elegir(activa); } else { pedir(); } }
        else if (e.key === 'Escape') { cerrar(); }
      });
      if (quitar) {
        quitar.addEventListener('click', function () {
          valor.value = '';
          if (elegido) { elegido.hidden = true; }
          buscar.hidden = false;
          buscar.focus();
          valor.dispatchEvent(new Event('change', { bubbles: true }));
          if (alElegir) { alElegir(null); }
        });
      }
    }
    $$('[data-kpc-articulo]').forEach(function (caja) { buscadorArticulo(caja, null); });

    /* ---------- El regalo de una fila: «Cambiar regalo» lo apunta en regalo_parejas (JSON clave → {r}) ---------- */
    var parejasCampo = campo('regalo_parejas');
    function leerParejas() { try { return JSON.parse((parejasCampo && parejasCampo.value) || '{}') || {}; } catch (e) { return {}; } }
    function escribirParejas(p) {
      if (!parejasCampo) { return; }
      parejasCampo.value = Object.keys(p).length ? JSON.stringify(p) : '';
      parejasCampo.dispatchEvent(new Event('input', { bubbles: true }));
    }
    function plantillaArticulo() {
      var caja = el('div', 'kpc-articulo');
      caja.setAttribute('data-kpc-articulo', '');
      caja.innerHTML = '<input type="hidden" data-kpc-articulo-valor>'
        + '<div class="kpc-articulo__elegido" data-kpc-articulo-elegido hidden><span class="kpc-articulo__texto"><strong data-kpc-articulo-texto></strong><small data-kpc-articulo-detalle></small></span>'
        + '<button type="button" class="kpc-ficha__quitar kpc-articulo__quitar" data-kpc-articulo-quitar aria-label="Quitar">×</button></div>'
        + '<input type="search" class="form-control" placeholder="Busca el regalo de esta fila: nombre, EAN, id…" autocomplete="off" spellcheck="false" data-kpc-articulo-buscar>'
        + '<ul class="kpc-opciones" data-kpc-opciones hidden></ul>';
      return caja;
    }
    raiz.addEventListener('click', function (e) {
      var b = e.target.closest('[data-kpc-cambiar-regalo]');
      if (!b || !parejasCampo) { return; }
      e.preventDefault();
      var clave = b.getAttribute('data-kpc-cambiar-regalo');
      var fila = b.closest('tr');
      var celda = fila ? fila.querySelector('[data-kpc-celda-regalo]') : null;
      if (!celda || celda.querySelector('[data-kpc-articulo]')) { return; }
      var nuevo = celda.querySelector('[data-kpc-regalo-nuevo]');
      if (!nuevo) {
        nuevo = el('span', 'kpc-regalo-nuevo');
        nuevo.setAttribute('data-kpc-regalo-nuevo', '');
        nuevo.hidden = true;
        celda.appendChild(nuevo);
      }
      var caja = plantillaArticulo();
      celda.appendChild(caja);
      buscadorArticulo(caja, function (a) {
        var p = leerParejas();
        if (!a) { delete p[clave]; escribirParejas(p); return; }
        p[clave] = { r: a.valor };
        escribirParejas(p);
        fila.classList.add('is-regalo-cambiado');
        nuevo.textContent = '';
        nuevo.appendChild(document.createTextNode('Al guardar regalará: ' + textoDe(a) + ' (' + euros(a.precio) + ')'));
        var deshacer = el('span', 'kpc-deshacer', 'deshacer');
        deshacer.addEventListener('click', function () {
          var q = leerParejas();
          delete q[clave];
          escribirParejas(q);
          nuevo.hidden = true;
          fila.classList.remove('is-regalo-cambiado');
        });
        nuevo.appendChild(deshacer);
        nuevo.hidden = false;
        if (caja.parentNode) { caja.parentNode.removeChild(caja); }
      });
      var entrada = caja.querySelector('[data-kpc-articulo-buscar]');
      if (entrada) { entrada.focus(); }
    });

    /* ---------- Código y título sugeridos ---------- */
    var codigo = campo('codigo');
    var titulo = campo('merchant_titulo');
    var esNueva = !(parseInt(valor('id_kpy_coupon'), 10) > 0);
    var codigoTocado = !esNueva || (codigo && codigo.value !== '');

    function marcaUnica() {
      var n = buscadorMarcas && valor('elegir') === 'marca' ? buscadorMarcas.nombres() : [];
      return n.length === 1 ? n[0] : '';
    }
    function sugerirCodigo() {
      if (!codigo || codigoTocado || tipo() !== 'cupon') { return; }
      var p = String(Math.round(parseFloat(String(valor('porcentaje')).replace(',', '.')) || 0));
      var m = normalizar(marcaUnica()).replace(/[^a-z0-9]/g, '').toUpperCase().slice(0, 20 - p.length);
      codigo.value = m && p !== '0' ? m + p : '';
      enlace();
    }
    function tituloSugerido() {
      var m = marcaUnica();
      if (tipo() === 'regalo') {
        return ('Regalo con la compra' + (m ? ' en ' + m : '')).slice(0, 60);
      }
      var p = pct(valor('porcentaje'));
      if (!p || p === '0') {
        return '';
      }
      if (tipo() === 'segunda_unidad') {
        return '2a unidad al ' + p + ' %' + (m ? ' en ' + m : '');
      }
      return (p + ' % de descuento' + (m ? ' en ' + m : '') + ' con el codigo ' + (codigo ? codigo.value : '')).slice(0, 60);
    }
    function cuentaTitulo() {
      var cuenta = $('[data-kpc-cuenta]');
      if (!titulo || !cuenta) { return; }
      var sugerido = tituloSugerido();
      titulo.placeholder = sugerido ? 'Se pone solo: «' + sugerido + '»' : 'Se pone solo al guardar';
      var texto = titulo.value || sugerido;
      var mal = texto.length > 60 || /!/.test(texto) || /[A-ZÁÉÍÓÚÑ]{5,}/.test(texto);
      cuenta.textContent = texto.length + '/60' + (mal ? ' · ojo: largo, «!» o MAYÚSCULAS' : '');
      cuenta.classList.toggle('kpc-rojo', mal);
    }
    if (codigo) {
      codigo.addEventListener('input', function () {
        codigoTocado = true;
        var pos = codigo.selectionStart;
        codigo.value = codigo.value.toUpperCase().replace(/\s+/g, '');
        try { codigo.setSelectionRange(pos, pos); } catch (e) { /* nada */ }
        enlace();
      });
    }

    /* ---------- El enlace que deja puesto el cupón ---------- */
    function enlace() {
      var e = $('[data-kpc-enlace]');
      if (!e) { return; }
      e.textContent = e.getAttribute('data-kpc-tienda') + '?' + e.getAttribute('data-kpc-parametro') + '=' + ((codigo && codigo.value) || 'CODIGO');
    }

    /* ---------- Fechas ---------- */
    function iso(d) {
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function leerFecha(t) {
      var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(t || '');
      return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
    }
    $$('[data-kpc-dias]').forEach(function (b) {
      b.addEventListener('click', function () {
        var dias = b.getAttribute('data-kpc-dias');
        var desde = campo('fecha_desde');
        var hasta = campo('fecha_hasta');
        if (dias === '0') { hasta.value = ''; cambio(); return; }
        var inicio = leerFecha(desde.value) || new Date();
        if (!desde.value) { desde.value = iso(inicio); }
        var fin = dias === 'fin'
          ? new Date(inicio.getFullYear(), inicio.getMonth() + 1, 0)
          : new Date(inicio.getFullYear(), inicio.getMonth(), inicio.getDate() + parseInt(dias, 10) - 1);
        hasta.value = iso(fin);
        cambio();
      });
    });
    function fechaCorta(t) {
      var d = leerFecha(t);
      return d ? String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0') : '';
    }

    /* ---------- Canales ---------- */
    function segunEntra() {
      var caja = $('[data-kpc-canales]');
      if (!caja) { return; }
      var porCanal = valor('entra') === 'canal';
      caja.classList.toggle('is-apagado', !porCanal);
      $$('input', caja).forEach(function (c) { c.disabled = !porCanal; });
      if (porCanal && !$$('input:checked', caja).length) {
        $('input', caja).checked = true;
      }
    }

    /* ---------- Fichero ---------- */
    var fichero = $('[data-kpc-fichero]');
    if (fichero) {
      fichero.addEventListener('change', function () {
        var e = $('[data-kpc-fichero-nombre]');
        if (e) { e.textContent = fichero.files && fichero.files[0] ? fichero.files[0].name + ' — se lee al Comprobar o Guardar' : ''; }
      });
    }

    /* ---------- La frase de abajo: qué se va a guardar ---------- */
    function marcadas(nombreCampo) {
      return $$('input[name="' + nombreCampo + '"]:checked').map(function (c) { return c.parentNode.querySelector('span').textContent.trim(); });
    }
    function resumen() {
      var e = $('[data-kpc-resumen]');
      if (!e) { return; }
      var t = tipo();
      if (!t) { e.textContent = ''; return; }
      var partes = [];
      var p = pct(valor('porcentaje')) || '?';
      if (t === 'special_price') {
        var m = modo();
        var nombreSp = { liquidacion: 'Liquidación', outlet: 'Outlet', bajada: 'Bajada de precio' }[valor('etiqueta')] || 'Special Price';
        partes.push(m === 'pct' ? nombreSp + ' −' + p + ' %'
          : nombreSp + ' con ' + (m === 'pct_articulo' ? 'el % de cada artículo' : 'el precio de cada artículo')
            + (parseFloat(String(valor('porcentaje')).replace(',', '.')) > 0 ? ' (el resto −' + p + ' %)' : ''));
      } else if (t === 'regalo') {
        var mismoCampo = campo('regalo_mismo');
        var mismoCaja = mismoCampo && mismoCampo.closest ? mismoCampo.closest('[data-kpc-articulo]') : null;
        var mismoTexto = mismoCaja ? ($('[data-kpc-articulo-texto]', mismoCaja) || {}).textContent : '';
        partes.push('Regalo: ' + (valor('regalo_modo') === 'mismo'
          ? (mismoCampo && mismoCampo.value ? (mismoTexto || mismoCampo.value) : 'FALTA EL ARTÍCULO')
          : 'el pequeño de su ficha')
          + (valor('regalo_por') === 'pedido' ? ' · uno por pedido' : ' · uno por unidad'));
      } else {
        partes.push(t === 'segunda_unidad' ? '2ª unidad al ' + p + ' %' : 'Cupón −' + p + ' %' + (codigo && codigo.value ? ' · ' + codigo.value : ' · sin código'));
      }
      var como = valor('elegir');
      var que = [];
      if (como === 'marca') {
        (buscadorMarcas ? buscadorMarcas.nombres() : []).forEach(function (n) { que.push(n); });
        var gamas = marcadas('categorias[]').filter(function (x, i, todas) { return todas.indexOf(x) === i; });
        if (gamas.length) { que.push(gamas.length === 1 ? gamas[0] : gamas.length + ' gamas'); }
        var tams = marcadas('tamanos[]');
        if (tams.length) { que.push(tams.length <= 3 ? tams.join(', ') : tams.length + ' formatos'); }
      } else if (como === 'lista') {
        var lineas = (valor('lista') || '').split(/\n/).filter(function (l) { return l.trim(); }).length;
        var excel = fichero && fichero.files && fichero.files[0];
        if (lineas || excel) { que.push(lineas ? 'lista de ' + lineas : 'el Excel ' + excel.name); }
      }
      partes.push(que.length ? que.join(' + ') : (como ? 'FALTAN LOS PRODUCTOS' : 'falta elegir los productos'));
      if (t === 'segunda_unidad') {
        partes.push('solo sueltos');
      } else if (t === 'regalo') {
        partes.push(valor('regalo_packs') ? 'los packs también regalan' : 'solo sueltos');
      } else if (!valor('incluir_packs')) {
        partes.push('sin packs');
      } else if (como === 'marca' && marcadas('packs[]').length) {
        partes.push('solo ' + marcadas('packs[]').join(' y ').toLowerCase());
      }
      var d = fechaCorta(valor('fecha_desde'));
      var h = fechaCorta(valor('fecha_hasta'));
      partes.push((d ? 'del ' + d : 'desde ya') + (h ? ' al ' + h : (t === 'special_price' ? ', FALTA LA FECHA DE FIN' : ', sin fin')));
      // El cupo: lo que cuenta cada tipo (ver KpcCupo::UNIDAD).
      var cupo = parseInt(valor('cupo'), 10) || 0;
      if (cupo > 0) {
        partes.push('cupo de ' + cupo + ' ' + ({ regalo: 'regalos', segunda_unidad: 'uds. rebajadas', cupon: 'uds. con cupón' }[t] || 'uds.'));
      }
      var cupoLista = document.querySelector('input[name="cupo_lista"]');
      if (cupoLista && cupoLista.checked && t !== 'regalo') {
        partes.push('unidades por artículo, de la lista');
      }
      var fuera = (valor('excluir') || '').split(/\n/).filter(function (l) { return l.trim(); }).length;
      if (fuera) { partes.push('menos ' + fuera); }
      if (t === 'cupon') {
        var entra = valor('entra');
        if (entra === 'todos') { partes.push('puesto para todos'); }
        else if (entra === 'canal') {
          var c = $$('input[name="canales[]"]:checked').map(function (x) { return x.parentNode.textContent.trim().split(' ')[0]; });
          partes.push('puesto si llega de ' + (c.join(', ') || '…'));
        }
      }
      if (!valor('activo')) { partes.push('APAGADA'); }
      e.textContent = partes.join(' · ');
    }

    /* ---------- Todo lo que cambia ---------- */
    var sucio = false;
    function cambio() {
      sucio = true;
      segunModo();
      placeholderLista();
      segunEntra();
      sugerirCodigo();
      cuentaTitulo();
      resumen();
    }
    if (form) {
      form.addEventListener('input', cambio);
      form.addEventListener('change', cambio);
      form.addEventListener('submit', function () { sucio = false; });
      window.addEventListener('beforeunload', function (e) {
        if (sucio) { e.preventDefault(); e.returnValue = ''; }
      });
    }
    placeholderLista();
    segunEntra();
    enlace();
    cuentaTitulo();
    resumen();

    /* ---------- La lista de promociones: el ojo, las terminadas y los productos de cada una ---------- */
    $$('[data-kpc-plegar]').forEach(function (b) {
      var cuerpo = document.getElementById(b.getAttribute('data-kpc-plegar'));
      if (!cuerpo) { return; }
      b.addEventListener('click', function () {
        var abrir = cuerpo.hidden;
        cuerpo.hidden = !abrir;
        b.setAttribute('aria-expanded', abrir ? 'true' : 'false');
      });
    });
    var terminadas = $('[data-kpc-terminadas]');
    if (terminadas) {
      var n = $$('tr[data-kpc-estado="terminada"]').length;
      terminadas.parentNode.lastChild.textContent = ' Ver terminadas (' + n + ')';
      terminadas.addEventListener('change', function () {
        $$('tr[data-kpc-estado="terminada"]').forEach(function (tr) { tr.hidden = !terminadas.checked; });
      });
    }

    // «Productos»: se despliegan bajo su fila, sin tocar el formulario. Se piden sin recargar la
    // página; si no llegan (la sesión ha caducado, por ejemplo), se va al enlace, que los trae igual.
    raiz.addEventListener('click', function (e) {
      var b = e.target.closest('[data-kpc-ver]');
      if (!b || !window.fetch) { return; }
      e.preventDefault();
      var id = b.getAttribute('data-kpc-ver');
      var fila = b.closest('tr');
      var sub = raiz.querySelector('tr[data-kpc-sub="' + id + '"]');
      if (sub) {
        sub.hidden = !sub.hidden;
        b.setAttribute('aria-expanded', sub.hidden ? 'false' : 'true');
        return;
      }
      sub = el('tr', 'kpc-sub');
      sub.setAttribute('data-kpc-sub', id);
      var celda = el('td');
      celda.colSpan = fila.children.length;
      celda.appendChild(el('p', 'kpc-cargando', 'Cargando sus productos…'));
      sub.appendChild(celda);
      fila.parentNode.insertBefore(sub, fila.nextSibling);
      b.setAttribute('aria-expanded', 'true');
      fetch(b.getAttribute('data-kpc-fragmento'), { credentials: 'same-origin' })
        .then(function (r) {
          if (!r.ok) { throw new Error('HTTP ' + r.status); }
          return r.text();
        })
        .then(function (html) {
          // Lo de verdad trae la caja de productos; otra cosa (la página de entrar) no vale.
          if (html.indexOf('kpc-productos') === -1) { throw new Error('sin productos'); }
          celda.innerHTML = html;
          activarProductos(celda);
        })
        .catch(function () { window.location.href = b.href; });
    });
    raiz.addEventListener('click', function (e) {
      var b = e.target.closest('[data-kpc-copiar], [data-kpc-copiar-texto], [data-kpc-confirmar]');
      if (!b) { return; }
      if (b.hasAttribute('data-kpc-confirmar')) {
        if (!window.confirm(b.getAttribute('data-kpc-confirmar'))) { e.preventDefault(); }
        return;
      }
      var texto = b.getAttribute('data-kpc-copiar-texto');
      if (texto === null) {
        var origen = $(b.getAttribute('data-kpc-copiar'));
        texto = origen ? (origen.value !== undefined ? origen.value : origen.textContent) : '';
      }
      var hecho = function () {
        var antes = b.textContent;
        b.textContent = 'Copiado ✓';
        setTimeout(function () { b.textContent = antes; }, 1400);
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(hecho);
      } else {
        var t = document.createElement('textarea');
        t.value = texto;
        document.body.appendChild(t);
        t.select();
        try { document.execCommand('copy'); hecho(); } catch (err) { /* nada */ }
        document.body.removeChild(t);
      }
    });

    /* ---------- Los productos de la promoción: buscar, quitar y volver a meter ---------- */
    var excluir = campo('excluir');
    var cambiosEl = $('[data-kpc-cambios]');
    var cambios = { quitados: 0, metidos: 0 };

    function lineas(texto) { return String(texto || '').split(/\r?\n/).map(function (l) { return l.trim(); }).filter(Boolean); }
    function pintarCambios() {
      if (!cambiosEl) { return; }
      var partes = [];
      if (cambios.quitados) { partes.push('quitas ' + cambios.quitados); }
      if (cambios.metidos) { partes.push('vuelves a meter ' + cambios.metidos); }
      cambiosEl.hidden = !partes.length;
      cambiosEl.textContent = partes.length ? 'Sin guardar: ' + partes.join(' y ') + '. Pulsa Guardar para aplicarlo.' : '';
    }
    function avisarCambio() {
      // Que se vea en el formulario y cuente como cambio (para el aviso de salir sin guardar).
      var caja = document.getElementById('kpc-excluir');
      if (caja) { caja.open = true; }
      excluir.dispatchEvent(new Event('input', { bubbles: true }));
      pintarCambios();
    }

    raiz.addEventListener('click', function (e) {
      var quitar = e.target.closest('[data-kpc-quitar]');
      var meter = e.target.closest('[data-kpc-meter]');
      if ((!quitar && !meter) || !excluir) { return; }
      e.preventDefault();
      var fila = (quitar || meter).closest('tr');

      if (quitar) {
        var id = quitar.getAttribute('data-kpc-quitar');
        var hay = lineas(excluir.value);
        if (fila.classList.contains('is-quitada')) {
          // Deshacer: fuera de «dejar fuera» otra vez.
          excluir.value = hay.filter(function (l) { return l !== id; }).join('\n');
          fila.classList.remove('is-quitada');
          quitar.textContent = 'Quitar';
          cambios.quitados = Math.max(0, cambios.quitados - 1);
        } else {
          if (hay.indexOf(id) === -1) { hay.push(id); }
          excluir.value = hay.join('\n');
          fila.classList.add('is-quitada');
          quitar.textContent = 'Deshacer';
          cambios.quitados += 1;
        }
        avisarCambio();
        return;
      }

      // Volver a meter: se borra de «dejar fuera» lo que lo nombra (su id o su EAN).
      var suyo = meter.getAttribute('data-kpc-meter');
      var ean = (meter.getAttribute('data-kpc-ean') || '').replace(/^0+/, '');
      var nombres = [suyo, suyo.replace(/-0$/, '')];
      var antes = lineas(excluir.value);
      var despues = antes.filter(function (l) {
        var limpio = l.split(/[;\t]/)[0].trim();
        return nombres.indexOf(limpio) === -1 && !(ean && limpio.replace(/^0+/, '') === ean);
      });
      if (despues.length === antes.length) {
        meter.textContent = 'Está en «Dejar fuera» por su referencia: bórralo de ahí';
        meter.disabled = true;
        return;
      }
      excluir.value = despues.join('\n');
      fila.classList.add('is-vuelve');
      meter.textContent = 'Vuelve al guardar';
      meter.disabled = true;
      cambios.metidos += 1;
      avisarCambio();
    });

    /** El buscador de una caja de productos (la de debajo del formulario o una desplegada en la lista). */
    function activarProductos(caja) {
      var filtrar = $('[data-kpc-filtrar]', caja);
      if (!filtrar || filtrar._activo) { return; }
      filtrar._activo = true;
      var filas = $$('tr[data-kpc-fila]', caja);
      var cuenta = $('[data-kpc-cuenta-filas]', caja);
      filas.forEach(function (tr) { tr._texto = normalizar(tr.textContent); });
      var espera = 0;
      filtrar.addEventListener('input', function () {
        clearTimeout(espera);
        espera = setTimeout(function () {
          var palabras = normalizar(filtrar.value).split(' ').filter(Boolean);
          var vistas = 0;
          filas.forEach(function (tr) {
            var si = palabras.every(function (p) { return tr._texto.indexOf(p) !== -1; });
            tr.hidden = !si;
            if (si) { vistas += 1; }
          });
          if (cuenta) { cuenta.textContent = palabras.length ? vistas + ' de ' + filas.length + ' filas' : filas.length + ' filas'; }
        }, 120);
      });
      // Buscar en la tabla no es un cambio de la promoción.
      filtrar.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
      filtrar.addEventListener('input', function (e) { e.stopPropagation(); });
    }
    $$('.kpc-productos').forEach(activarProductos);

    // Tras «Comprobar», directo a los productos (si no hay errores que leer arriba).
    var previa = document.getElementById('kpc-previa');
    if (previa && form && form.hasAttribute('data-kpc-comprobado') && !$('.alert-danger')) {
      previa.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
})();
