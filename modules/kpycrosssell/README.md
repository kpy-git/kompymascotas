# kpycrosssell · Caprichitos

Se llama **«Kompy · Caprichitos»** desde la 1.5.0 (Rafa, 30/09/2026; antes «Llévalo junto»). El nombre
técnico, `kpycrosssell`, no cambia: con él van sus tablas, su configuración y el enganche de la ficha.

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