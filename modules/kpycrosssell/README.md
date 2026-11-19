# kpycrosssell · Caprichitos

Se llama **«Kompy · Caprichitos»** desde la 1.5.0 (Rafa, 30/09/2026; antes «Llévalo junto»). El nombre
técnico, `kpycrosssell`, no cambia: con él van sus tablas, su configuración y el enganche de la ficha.

## Desde la 1.7.0: la cesta de un solo almacén

Rafa, 16–18/11/2026: que el pedido no se retrase por mezclar almacenes. La regla de qué almacén implica
cada producto es de Julián (`kpyorderdispatcher`), no nuestra, y así lo pidió en su correo del 17/11:
**este módulo solo ofrece alternativas**, no calcula almacenes, ni fechas, ni stock.

- **La puerta** es `WarehouseFinder::hasMultiWarehouse($products)` (desde el 19/11 sus funciones ya no
  llevan el id del carrito, solo los productos): se le pasan los de la cesta presentada
  (`CartProductLazyArray`, con unidades y `total_wt`; `KcsAlmacenes::productosDeCesta`), como hace su
  `kpyproductavailabilitymessages`. Si dice que la cesta no implica más de un almacén (también cuando cada
  almacén pasa del envío gratis, 39,90 €, y se parte el pedido), no se pinta nada. Si lo implica, el
  **almacén de referencia** es el que cubre más euros de la cesta (a igual importe, el de la línea más
  cara); las líneas que no salen de él **bloquean**. El almacén de cada producto se le pregunta a su
  `getWarehousesByCart([$producto])` con ese solo producto (un objeto con `id_product`,
  `id_product_attribute`, `id_manufacturer` y `quantity`, las unidades de la línea; a un candidato, las de
  la línea que sustituiría: su prioridad de tienda depende de ellas).
  Sus clases no cargan hasta que PrestaShop instancia sus módulos: `KcsAlmacenes` los instancia antes.
  Si su módulo no está, o algo falla, el nuestro se calla (una línea en el registro) y la cesta sigue.
- **En cada línea que bloquea**: la etiqueta «Sale de otro almacén y puede retrasar tu pedido» con su (i)
  (`displayCartExtraProductInfo`) y el bloque «Cámbialo por…» (`displayCartExtraProductActions`, detrás de
  `kpycoupons`): tres alternativas, «Ver más productos elegibles», «Dejarlo como está» (se recuerda en esa
  cesta) y «Productos de estas marcas no retrasan tu pedido» con enlace a `/marcas/<marca>`. Al pie de la
  lista, el aviso ámbar (`displayShoppingCartFooter`). En la ventana «Añadido a tu cesta», lo mismo para
  el producto que acaba de entrar (`displayCartModalFooter`; la hoja lo pone encima de los botones).
  **Una línea que bloquea sin alternativas no lleva nada** (ni etiqueta, ni aviso, ni en la ventana;
  `KcsCesta::seEnsena`): la cesta se queda como si todo saliera del mismo almacén, porque la fecha y su
  nota ya las pone el módulo de Julián y no hay que duplicar mensajes (Rafa, 18/11). Si el cliente pulsó
  «Dejarlo como está», la etiqueta con la (i) se queda. **Nunca** se nombra el almacén ni se da una fecha.
- **Las alternativas** (`KcsAlternativas`): mismo «Tipo de Producto», misma mascota (también por el
  nombre), la misma patología **exacta** (en las dietas veterinarias, todas las necesidades salvo
  Vitaminas, Alta actividad y Sabor, y «Problemas de pelo y piel» es etiqueta de síntoma: no iguala, pero
  la dieta que coincide del todo va primero; sin patología, nada. Fuera de las dietas solo son exactas las
  enfermedades de verdad —renal, hepático, cardiaco, diabetes, leishmania, recuperación, cerebral—: un
  snack «para alérgicos» o un pienso «digestión sensible» son perfil), edad compatible, en pienso el mismo
  tamaño de perro, con stock para la cantidad, de marca permitida, y **del almacén de referencia** según
  Julián, menos lo que su reparto de **pedidos** no deja salir de ese almacén: los snacks de Natural
  Greatness (categoría 5576) no salen de Evolution Pet (su `EvolutionOrderGuard`). Es el ajuste
  «Categorías que nunca salen de un almacén» (`KCS_CESTA_NUNCA`, `EVOLUTION:5576`; Rafa, 18/11 a
  mediodía, con Natural Greatness + sus galletas delante); desde el 19/11 su `isEvolutionProduct` ya los
  excluye también en `getWarehousesByCart`, y el ajuste se queda como estaba. Nunca antiparasitarios ni
  medicamentos. El **tema** (`KcsPistas::temas`, por el nombre y la
  ficha: dental, piel, articular, calma, peso, esterilizado, digestivo, urinario, bolas de pelo, alergia)
  ordena: a un snack dental otro dental antes que uno genérico, y uno para adelgazar el último (solo si
  no hay otra cosa: Rafa prefiere una alternativa a ninguna).
- **El precio es un orden, no un muro** (Rafa, 18/11: «veo pocas alternativas»): se compara **lo que se
  paga** (con impuestos y promociones) con lo que vale la línea (sin su promoción de hoy). Primero los
  formatos de **precio parecido** (70–130 %, y en comida nunca más caro por kilo que ese 130 %; en snacks,
  complementos y accesorios el peso de la ficha no dice nada y no se mira); si no hay o son pocos, los más
  cercanos en precio dentro de la banda **«como mucho»** (del 25 % al 400 %, o hasta 10 € más si es más
  que el 400 %: a un snack de 1,55 € el 400 % son 6 €), marcados como no parecidos, y el texto del bloque
  deja de prometer «precio parecido». Y en comida, el formato que cuesta lo mismo **por kilo** aunque sea
  de otro tamaño (a una lata suelta de 4,70 €, un pack de seis tarrinas de 21 €) entra también como
  lejano. Un snack dental de 3,29 € puede tener como alternativa un VeggieDent de 8,99 €: el precio lo ve
  el cliente en la tarjeta.
- **Orden** («que más o menos sea lo mismo»): marcas preferidas para dietas (Farmina); mismo tema antes
  que genérico, y otro tema el último; precio parecido antes que lejano; la ficha clínica que coincide del
  todo; cuantas más **categorías de la tienda** comparta con la línea (las de «Comprar por mascota» del
  cuarto nivel en adelante, nunca las de marca; la principal cuenta doble: «Snacks dentales perros»,
  «Pienso natural perros»…); misma **talla** de perro (ficha o nombre: «perros grandes»; entre los formatos
  de un producto en tallas S/M/L, los de su talla primero); la misma opción nutricional, ingrediente
  principal y textura de la ficha; precio más cercano; ventas. Y **variedad** en las tarjetas a la vista:
  como mucho dos de la misma marca si hay otra igual de buena. Hasta 12.
- **Los candidatos** se cogen del catálogo por cercanía de precio de tarifa (300, no los 120 más vendidos:
  lo de Neftys vende poco y se quedaba fuera), y el almacén de cada formato se mira en la **memoria**
  `kpy_cross_sell_almacen` (las respuestas del reparto de Julián de las últimas 6 horas, por producto y
  formato; lo que no está se le pregunta y se apunta). Lo que se va a enseñar se confirma en vivo. En
  pruebas: 80–130 ms por línea con la memoria caliente, ~500 ms la primera vez.
- **Cambiar** (`controllers/front/cambiar.php`): entra la alternativa con la misma cantidad y, solo si
  ha entrado, sale la línea; el candidato tiene que seguir siendo alternativa **en ese momento**. Con
  JavaScript, sin recargar (se avisa a la tienda con `updateCart` y repinta la cesta; el aviso del pie se
  pide al servidor, `accion=estado`); sin JavaScript, son formularios y la (i) y «Ver más» son `<details>`.
- **Se cuenta** en `kpy_cross_sell_cesta` (vistas, cambios, «dejar», «ver más», (i), errores) y se ve en
  la pantalla del módulo, que tiene también **«Probar una cesta»** por su número (con tema, precio parecido
  o no y categorías compartidas de cada alternativa, y cuánto recuerda la memoria de almacenes) y los
  ajustes (encendido, ventana, aviso, banda de precio parecido 70–130 %, banda «como mucho» 25–400 % o
  10 € más, tarjetas, tope, marcas, marcas preferidas para dietas, categorías que nunca salen de un
  almacén).
- **Probado** el 18/11 en pruebas con `tests/cesta.php` (sin tienda), `bin/herramientas/pruebas-cesta-
  alternativas.php` (en el contenedor) y `bin/herramientas/pw-cesta.mjs` (Chrome, Firefox, WebKit, iPhone;
  ventana, cesta, cambiar, dejar, ver más, sin JavaScript, el snack dental con alternativa más cara, la
  dieta hipoalergénica con tres dietas de Neftys, el snack natural de 1,55 € y la cesta sin alternativas,
  Natural Greatness + Dentalife, donde no se enseña nada). Textos con `{l}` (se cambian desde el back
  office), estilos por `actionFrontControllerSetMedia`, nada en el tema.

## Desde la 1.5.0

- **La foto y el nombre de cada sugerencia llevan a su ficha**, en la misma pestaña (en un
  antiparasitario, con el peso elegido). **«Atrás» vuelve con todo como estaba**: formato, cantidad y
  altura de la página los repone el navegador (comprobado en Chrome, PC y móvil). El peso elegido de
  un antiparasitario también, pero antes la tarjeta no se enteraba: enseñaba el peso elegido y el
  «+» habría añadido otro. Ahora `junto.js` ajusta cada tarjeta a su desplegable al cargar **y otra
  vez en `load`** (Chrome repone los desplegables justo antes de `load`, después de que corra el
  script: medido el 30/09) y, si el navegador no lo repuso, lo recupera de la pestaña
  (`sessionStorage`). Probado en pruebas, PC y móvil, entrando por la foto y por el nombre.
- **Al subir una versión nueva, vaciar la caché del tema**: `junto.js` y `junto.css` van dentro de
  los ficheros combinados del tema (`themes/kpy_theme/assets/cache/`) y, sin vaciarla, la tienda
  sigue sirviendo los de antes.
- **Reglas de «llevados juntos»** (`KCSJ-{cesta}-{firma}`, una por cesta, como la 2ª unidad de
  `kpycoupons`): duran hasta el fin del descuento de la marca (antes un mes), no se reescriben si no
  cambia nada, se barren solas cada 6 horas (cestas abandonadas más de 2 días, caducadas, sin
  descuento en marcha o ya pedidas sin usarla) y se rehacen al ver la cesta o el pago.

## Cómo funciona desde la 1.4.0: se calcula una vez y se guarda

Las tres sugerencias de cada producto **no se calculan al abrir la ficha**. Se calculan una vez y se
guardan en los **productos relacionados** de PrestaShop (tabla `accessory`), que son los que se ven
y se editan en el back office, en la ficha de cada producto. La ficha solo las lee: una consulta.

| Pieza | Qué hace |
|---|---|
| `accessory` (del core) | **qué** productos se sugieren. Se puede quitar o poner a mano en el back office |
| `kpy_cross_sell_auto` | **en qué orden** (`accessory` no lo guarda) y qué dejó puesto el cálculo |
| `bin/generar.php` | calcula y guarda. `--nuevos` solo los que no tienen nada; `--producto=N` uno; `--forzar` también los tocados a mano |
| `bin/auditar.php` | revisa **todas** las parejas guardadas buscando errores, sin usar el motor |
| `hookActionProductAdd` | un producto creado desde el back office nace con las suyas |

**Lo que se toca a mano se respeta.** Si los relacionados de un producto ya no son los que dejó el
cálculo, alguien los ha cambiado en el back office, y `generar.php` no vuelve a tocar ese producto
(salvo `--forzar`). Para devolvérselo al cálculo, basta con vaciar sus relacionados.

**Los productos que entran por los paquetes de catálogo** (SQL directo) no disparan el hook de alta:
los recoge `generar.php --nuevos`, que conviene pasar cada noche. De vez en cuando, entero, para que
las sugerencias mejoren con el stock y las altas nuevas.

**Qué formato se ofrece**: la unidad mínima de venta —el formato más barato con stock que **no esté
apagado** en `kpy_product_attribute`—, salvo Boske y Natural Greatness, que van en pack de 6. Se
decide al pintar, porque el stock cambia durante el día. Los antiparasitarios van **por peso**
(`KcsAntiparasitarios` + `KcsBloque::conPesos`): solo los pesos que encajan con el formato que se
mira y, si encaja más de uno, la tarjeta pide elegir; nunca se añade una dosis a ciegas.

**El «+» añade directo** a la cesta (`controllers/front/anadir.php`), la tarjeta se queda en verde
con su ✓, y lo que ya está en la cesta sale en verde al cargar. No hay pie ni botón aparte.

**De qué distribuidor es cada marca** sale de las tablas de stock de los conectores (`neftys_stock` y
`kpy_distrivet_stock`); si una marca la sirven los dos, Neftys. Evolution (Natural Greatness, Natural
Woodland, Farm Nature, Alpha Spirit, Primal Spirit) va por nombre. Todo lo demás es tienda.

El bloque de la ficha que ofrece lo que pega con lo que se está mirando: a un pienso, el húmedo de
su marca, unos snacks o un complemento. De un toque entra en la cesta y, si se lleva el par, hay un
porcentaje extra en los dos.

## De dónde salen las sugerencias

Kompy no tenía relaciones entre productos: la tabla de PrestaShop (`accessory`) está **vacía** y las
del web antiguo se hicieron a mano y se quedaron viejas. Pero el catálogo sí sabe de cada producto
su **marca**, su **mascota** y su **tipo** (Comida Seca, Comida Húmeda, Snacks, Complemento…), y
muchos llevan además **edad** y **tamaño**. Con eso se deduce.

Tres pasos, y manda el primero que encuentre algo:

| | Qué busca | Cuándo |
|---|---|---|
| **1. A mano** | Las parejas apuntadas en la pantalla del módulo | Siempre ganan |
| **2. De su marca** | Misma marca y misma mascota, con un tipo que complemente al principal | Lo normal |
| **3. De reserva** | Las marcas del mismo distribuidor, por orden | Si su marca no tiene nada |

El tercer hueco va por este orden: **suplemento** de su patología → **snack funcional** de esa misma
patología → **snack natural** (Opción nutricional «Natural» o «Sin cereales»). Nunca un snack de
bazar: era lo que hacía salir Trixie en media tienda.

En el catálogo de pruebas, **el 78 % de los 1.250 piensos** tiene algo de su propia marca que
ofrecer (Royal Canin 129 piensos / 51 húmedos, Hill's 79 / 45, Farmina 94 / 26, Eukanuba 51 / 18).

Reglas que no se saltan nunca, que son las que hacen que el bloque no moleste ni quede raro:

- **barato**: como mucho una parte del precio del principal (60 % por defecto) y con un tope en
  euros. A un pienso de 50 € se le ofrece algo de 30 € o menos; nunca otro saco;
- **con stock** y con precio;
- **nunca del mismo tipo**: al que mira un pienso no se le ofrece otro pienso;
- **ni una enfermedad que no sea la suya**: a un gato sano no se le ofrece comida de diabéticos, y a
  uno con problemas renales no se le ofrece una de hígado (`PATOLOGIAS`). Las necesidades que no son
  enfermedad —esterilizado, control de peso, alta actividad, vitaminas, bolas de pelo, estrés— sí se
  pueden cruzar;
- **ni una edad que no sea la suya**: a un pienso de adulto, nada de cachorro, y al revés tampoco
  (`ETAPAS`). «Todas las edades» vale para cualquiera;
- **ni higiene al lado de la comida**: un limpiador de ojos junto a un pienso no vende. La higiene
  solo acompaña a la higiene, a los accesorios y a la arena;
- **ni la mascota equivocada**: además de la característica, se mira el nombre, porque el catálogo
  marca 541 productos como «Perro y Gato» y muchos se llaman «… para perros»;
- **ni de otro distribuidor**: ver abajo.

## De dónde sale cada cosa: el distribuidor

Kompy no sirve todo desde el mismo sitio, y **mezclar parte el pedido en dos envíos**. Por eso una
sugerencia tiene que salir del mismo sitio que el producto que se está mirando (`KcsDistribuidor`):

| | Marcas | Piensos | Húmedos | Suplementos | Snacks |
|---|---|---|---|---|---|
| **Distrivet** | Acana, Orijen, Farmina, Lenda, Applaws, Taste of the Wild, Pedigree | 234 | 78 | 3 | 31 |
| **Neftys** | Eukanuba, Schesir, Lily's Kitchen, IAMS, Specific y **Boske** | 129 | 250 | 1 | 2 |
| **Evolution Pet** | Natural Greatness, Alpha Spirit | 29 | 45 | 0 | 13 |
| **Almacén** | Royal Canin, Ownat, Advance… y todo lo que no esté repartido | 858 | 439 | 150 | 199 |

**Boske es de Neftys**, que es su almacén principal, aunque haya stock en tienda: por eso al Boske de
gato se le ofrecen Schesir y Lily's. Y como es la marca de la casa, **entra igualmente en cualquier
ficha** (menos en Evolution). En el catálogo Boske solo tiene húmedo de **perro** (4 dietas
veterinarias), así que su pienso de gato tira siempre de la reserva de Neftys.

El dato sale, por este orden: Evolution por nombre, lo apuntado a mano en la pantalla, las tablas de
stock `neftys_stock` y `kpy_distrivet_stock` (Neftys gana si una marca está en las dos), y la lista
de Rafa del código (`DE_PARTIDA`) para las marcas que las tablas no traen, como Lily's Kitchen, IAMS
o Applaws. Lo que no está en ningún sitio es de la tienda.

Consecuencias que se ven en la tienda:

- las **marcas de reserva** son las del mismo distribuidor (Neftys tira de Schesir y Lily's;
  Distrivet de Farmina y Applaws; el almacén, de Boske y Royal Canin);
- **Evolution solo se cruza consigo mismo**: a un pienso de Natural Greatness se le ofrece su propio
  húmedo, y de momento ni suplementos ni caprichos de fuera;
- **Boske entra siempre** (es la marca de la casa), salvo con Evolution;
- **Natural Greatness se ofrece en pack de seis latas**, como Boske;
- si un hueco se queda vacío, se rellena **por escalones** para que salgan siempre tres: el mismo
  distribuidor sin techo de precio, después Boske y, como último recurso, la tienda (origen
  `tienda`). Evolution no pasa por los dos últimos. Nunca se salta mascota, edad, patología ni las
  marcas vetadas: si no hay nada que encaje, la ficha enseña menos.

Los caprichos se ordenan **por lo que más se vende**, no por precio, y se rotan según el producto que
se está mirando: si no, la tienda entera enseñaría los tres mismos snacks en todas las fichas.

## El bloque

Lo coloca el tema con el enganche `displayKpyCrossSell` (`catalog/product.tpl`, debajo del bloque
de compra, antes de la descripción), porque es donde se decide. Hasta la 1.5 lo llamaba
`kpyproductbuy` desde su caja; ese módulo ya no existe (23/10/2026).

En el **ordenador** (a partir de 992 px) el bloque sale de la columna de compra y se pone **a todo el
ancho**, debajo de las dos columnas: una tarjeta bajo la foto, otra en medio y otra bajo el botón de
comprar. Lo hace el propio `junto.js` moviendo el nodo, **sin tocar la plantilla del tema**; si algún
día se aplica `PARCHE-tema-junto-ancho.patch`, el bloque ya nace ahí y el script no mueve nada. Los
puntos de recompensa (el bloque lila de `gamifications`) se colocan entre el botón y las tarjetas. En
el móvil no se mueve nada: se queda dentro de la caja, que es como funciona bien.

**El título cambia según lo que se ofrece y para qué** (`KcsTitulo`): «Cuida su sistema urinario»,
«Ayuda a su función renal», «Cuida sus articulaciones»… cuando el producto tiene una necesidad y la
sugerencia la comparte; si no, «Completa su alimentación», «Dale un capricho», «Refuerza su
alimentación». El título **solo promete lo que la sugerencia cumple**. Dos tarjetas finas: foto, nombre, formato y, debajo, el precio con un botón **«+»** a su derecha que
se vuelve un visto al elegirlo. **Nada viene
marcado**: el cliente elige, y el pie con el total y el botón solo aparece cuando ha elegido algo.
En el móvil la fila entera es el área de toque.

Se ofrece siempre el **formato más barato con stock**: quien está comprando un saco de 50 € no
quiere elegir entre seis latas, quiere decir que sí a una y seguir.

## El descuento por llevarlos juntos

Un porcentaje extra **en los dos productos** cuando el cliente se lleva el par. **Se decide marca a
marca** en la pantalla del módulo (marca, %, fechas, «solo con productos de la misma marca» y su
interruptor). Mientras una marca no tenga descuento encendido, su bloque sugiere pero no descuenta.

PrestaShop no sabe expresar «compra A y B», así que se hace como la «2ª unidad» de `kpycoupons`: se
calcula el importe exacto y se mete en una regla de carrito **de esa cesta**, para que la suma, el
pedido y la factura los siga haciendo PrestaShop. Cada línea entra en **una sola** pareja, y una
línea que ya lleva un cupón o una «2ª unidad» se queda fuera: dos promociones sobre lo mismo es el
fallo que ya se corrigió en la cesta.

**En Google no se anuncia.** Merchant no tiene forma de decir «compra A y B»: sus promociones son
por producto o por cantidad del mismo producto. Anunciar un descuento que necesita un segundo
producto sería prometer algo que el robot no puede comprobar. Es una palanca de la tienda, no del
feed.

## Pantalla del módulo

En *Catálogo → Kompy · Caprichitos* (o *Módulos → Configurar*):

- los ajustes: cuántas sugerencias, precio máximo, tope y el descuento por llevarlos juntos;
- **probar un producto**: se busca **por id o por nombre**, como en el panel de siempre, y dice qué
  se le ofrecería ahora mismo, de dónde sale cada sugerencia y **cuánta gente se la lleva**
  (clics / veces que se ha enseñado). Es lo primero que se quiere mirar;
- en esa misma tabla, los dos botones del día a día: **Quitar** (ese producto no vuelve a salir en
  esa ficha) y **Fijar 1ª / 2ª / 3ª** (lo clava en ese hueco). Lo que no se toca lo sigue decidiendo
  el catálogo;
- las parejas a mano, que además de por producto valen para **una categoría entera o una marca
  entera**.

## Tablas

Crea las suyas y no toca ninguna otra:

- `kpy_cross_sell` (ambito, id_ref, id_sugerido, posicion, activo, **veto**) — lo puesto y lo quitado
  a mano. Al desinstalar se queda: es trabajo de catálogo, no del módulo;
- `kpy_cross_sell_promo` — el descuento por llevarlos juntos, marca a marca;
- `kpy_cross_sell_clic` (id_ref, id_sugerido, id_shop, vistas, clics, ultimo) — lo que se enseña y lo
  que se lleva la gente. No guarda nada del cliente.

## Probar

```
php tests/sugerencias.php                                  # las reglas, sin tienda
docker exec … php modules/kpycrosssell/bin/cobertura.php    # a cuántos productos les sale algo
```

## Instalar

1. Copiar la carpeta a `modules/kpycrosssell`.
2. `php bin/console prestashop:module install kpycrosssell`.
3. Comprobar los cuatro hooks: `displayKpyCrossSell` (lo llama el tema desde `catalog/product.tpl`),
   `actionFrontControllerSetMedia`, `actionCartSave` y `displayShoppingCart`.
4. Comprobar que aparece Catálogo → **Kompy · Caprichitos** (al actualizar desde la 1.4, `upgrade-1.5.0.php`
   le cambia el nombre).
5. Vaciar la caché.

Funciona solo: sin `kpycoupons` no hay nada que respetar, y sin parejas a mano las saca del catálogo.

**Desde la 1.6.0 (23/10/2026)**, sin llamadas a otros módulos (regla de Julián): los productos que ya
llevan una promoción de `kpycoupons` se preguntan por su hook de consulta
`actionKpyCouponsProductosConPromo` (este módulo lo lanza con `cart`, `id_shop` y `productos` por
referencia; `kpycoupons` lo contesta; sin él, nadie contesta), en vez de llamar a `KpcCarrito`. Y
`junto.js` coloca los puntos de `gamifications` junto al botón de comprar del tema
(`.product-actions__wrapper-add-to-cart`), no en la caja de `kpyproductbuy`. Sin tablas nuevas ni
`upgrade`: solo sube la versión.
