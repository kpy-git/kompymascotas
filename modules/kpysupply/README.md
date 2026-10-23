# kpysupply · Kompy · Acuerdos con proveedores

Lo que el proveedor nos da por una promoción, guardado **por artículo y con fechas**, para calcular
márgenes, contrastar facturas y cobrar las redenciones. Tres tipos:

| Tipo | Qué es | Valor |
|---|---|---|
| **Redención** | Al acabar, el proveedor paga un % de lo vendido sin IVA; se le pasa la lista de ventas y se le factura | % sobre las ventas sin IVA |
| **Descuento en factura** | Mientras dura, el proveedor factura con un % adicional sobre el coste neto (puede ser distinto por gama) | % sobre el coste neto de la unidad de venta |
| **Precio de coste actualizado** | El proveedor manda precios de coste nuevos por EAN, para un periodo o «hasta nueva orden» | € por unidad de venta, sin IVA |

No cambia nada de cara al cliente: no toca precios, cesta ni pedidos. Plan y decisiones:
`docs/plan-acuerdos-proveedor.md` del proyecto.

Necesita **`kpycoupons` 3.0.0 o posterior** (su contrato, `KpcContrato`: el hook de consulta
`actionKpyCouponsAlcance`, y en sus hooks de aviso los artículos y la fila pública de cada promoción
y el número del paso). La instalación se niega si la versión es menor, en los ficheros o en la base
de datos. **No llama a ninguna clase ni función de `kpycoupons`** (regla de Julián: ningún módulo
llama a funciones de otro): lo que necesita de una promoción le llega por esos hooks, la lectura de
listas es una copia propia (`KsLectura`) y lo poco que lee de sus tablas está declarado abajo («Lo
que lee de kpycoupons»).

## Cómo se usa

Catálogo → **Kompy · Acuerdos con proveedores**.

1. **En la promoción.** En Catálogo → Kompy · Promociones, cada promoción tiene un paso más,
   «Acuerdo con el proveedor»: sin acuerdo, redención X % o descuento en factura X %. Al guardar, el
   acuerdo se apunta para **cada artículo de la promoción** (los del paso 2) con **sus fechas**
   (paso 3), en el momento: el módulo le pide a `kpycoupons` los artículos de la promoción (hook de
   consulta) y los cuadra; vale también para una promoción apagada o terminada. Si la promoción se
   alarga, el acuerdo la sigue; si entran artículos nuevos (un producto nuevo de la marca, un formato
   que vuelve a estar a la venta), se les da en el siguiente reparto de promociones.
2. **Subiendo una lista.** En la pantalla, «Subir una lista del proveedor»: el tipo, las fechas (o
   «hasta nueva orden») o la promoción a la que va, y la lista pegada del Excel (o el fichero) con el
   EAN o la referencia y, si cada artículo trae el suyo, su % o su precio. **Comprobar** no guarda
   nada: dice de qué columna ha sacado cada cosa, qué fila es qué artículo, cuáles no se encuentran
   (con un botón para copiarlas y reclamarlas), cuáles se repiten con otro valor, cuáles ya estaban
   igual, qué precios no parecen de la unidad de venta y qué artículos ya tienen **otro acuerdo en
   esas fechas** (se elige: sustituir, que recorta el anterior para que no se pise, o saltar).
   **Guardar** escribe solo lo encontrado.
3. **Consultar.** Cada acuerdo (tipo + fechas + marca, y su promoción si viene de una):
   - **Artículos**: la lista, con el coste que tiene PrestaShop al lado; quitar un artículo,
     prolongar o cerrar (los sueltos; los de una promoción siguen a la promoción: se cambian allí) y,
     en los de una promoción, **«Poner al día con la promoción»** (le pide a `kpycoupons` los
     artículos y las fechas y cuadra el acuerdo; si `kpycoupons` no contesta, no toca nada y lo dice),
     borrar.
   - **Ventas**: lo vendido en su periodo, por artículo (unidades, de ellas de regalo, pedidos,
     ventas sin IVA, y en una redención el importe a cobrar; en un precio de coste, el coste de lo
     vendido y el margen), y pedido a pedido. Con **Bajar el Excel** (tres hojas: por artículo, por
     pedido y «qué es esto»).
   - **Excel**: la lista del acuerdo tal cual (EAN, referencia, producto, formato, valor, fechas).
4. **Ajustes**: qué estados de pedido no cuentan como venta (por defecto cancelado, error en el
   pago y reembolsado; se pueden añadir los propios, como «AQUA - Pedido eliminado para liberar
   mercancía»).

### Las reglas que conviene saber

- **La lista manda sobre el paso de la promoción.** Una promoción «Royal Canin −15 % en factura» y
  una lista enlazada a ella con la gama húmeda al 20 %: la húmeda va al 20 % y lo demás al 15 %.
- **El acuerdo de una promoción se cuadra al guardarla y con el botón; en cada reparto solo se
  añade.** Al guardar la promoción (también apagada o terminada) y con «Poner al día» se hace el
  espejo completo: fechas, tipo y %, artículos nuevos, y fuera los que ya no están en la promoción
  salvo los que vendieron. En los repartos de promociones (cada cambio de fecha de cualquier
  promoción) solo se ponen al día las fechas y se da el acuerdo a los artículos nuevos: nunca se
  quita nada. La promoción recién guardada pasa por los dos (al guardar y en el reparto que
  `kpycoupons` lanza detrás): la segunda vez no cambia nada.
- **Lo que ya vendió con el acuerdo no se quita solo.** Si un artículo sale de la promoción (se
  apaga por falta de stock, se excluye), su fila se queda si tiene ventas en el periodo; si no tiene,
  se quita al guardar la promoción o al ponerla al día.
- **Nada se borra al terminar.** Un acuerdo terminado se queda para consultarlo. Desinstalar el módulo
  tampoco borra nada.
- **Guardar es todo o nada.** Una lista, o el acuerdo de una promoción, se escribe en una transacción:
  si una fila no cabe, no se escribe ninguna y la pantalla dice por qué (también fuera del modo
  desarrollo, donde PrestaShop no lanza errores de base de datos: aquí se comprueba cada escritura).
- **Los artículos de una promoción son los de su paso 2**, estén o no rebajados por otra promoción
  (`kpycoupons` aparta de un cupón los que ya tienen Special Price; el acuerdo con el proveedor es
  sobre la gama, no sobre la mecánica del cupón). Los quitados a mano en la promoción, no. Los calcula
  `kpycoupons` y los da por sus hooks (`entran` + `especial`).
- **Un artículo, un acuerdo por periodo** (la clave única que pidió Julián: artículo + fechas). Dos
  acuerdos con fechas que se cruzan (una redención y un precio de coste, por ejemplo) sí pueden
  convivir; la pantalla lo avisa («N con otro»), porque dos redenciones a la vez cobrarían dos veces
  las mismas ventas.
- **De dónde sale el valor de cada fila.** De la columna elegida en la comprobación; si no, de la que
  su cabecera diga («Dto.», «%», «Neto», «Coste», «Precio»…; nunca de «PVP» para un precio de coste);
  y sin cabecera que lo diga, solo si la lista es «código + valor». Una tarifa de proveedor con una
  columna de kilos (9, 12, 7,5) no se lee como %. Los códigos se leen como EAN o referencias (la
  nuestra, la del proveedor, el MPN), nunca como ids de producto.

## Las tablas

Las dos de Julián, con tres matices y tres columnas más, y una pequeña para el paso de cada promoción:

```sql
kpy_supplier_promo_type (
    id, nombre                       -- 'redención', 'Dto en Factura', 'Precio de coste actualizado'
)
kpy_supplier_promo_products (
    id, id_product, id_product_attribute,   -- 0 = el producto entero (sin combinaciones)
    id_supplier_promo_type,                 -- FK al tipo
    value DECIMAL(20,6),                    -- % o € sin IVA por unidad de venta
    date_from DATETIME, date_to DATETIME,   -- 2099-12-31 23:59:59 = hasta nueva orden
    id_kpy_coupon NULL,                     -- de qué promoción de kpycoupons viene (NULL: suelto)
    origen,                                 -- 'promo' (el paso de la promoción) o 'lista' (subida)
    date_add, date_upd,
    UNIQUE (id_product, id_product_attribute, date_from, date_to)
)
kpy_supplier_promo_coupon (
    id_kpy_coupon PK, id_supplier_promo_type, value, date_add, date_upd   -- lo marcado en el paso
)
```

Los matices, y por qué:

- `value` es **DECIMAL**, no FLOAT: un FLOAT pierde céntimos (12,35 € se guarda como 12,3499…).
- Las fechas son **DATETIME** (como todo PrestaShop; un TIMESTAMP acaba en 2038 y cambia con la zona
  horaria del servidor).
- «Hasta nueva orden» es **2099-12-31 23:59:59** y no NULL: MySQL deja repetir filas con NULL en una
  clave única, y la de artículo + fechas tiene que valer siempre. Es lo mismo que usa `kpycoupons`
  para las promociones sin fin.
- `id_kpy_coupon` y `origen`: para que el acuerdo siga a su promoción (fechas, artículos nuevos) sin
  pisar lo subido por lista.

El acuerdo que vale hoy para un formato, por ejemplo (con el prefijo de producción):

```sql
SELECT t.nombre, s.value, s.date_from, s.date_to
FROM pps22_kpy_supplier_promo_products s
JOIN pps22_kpy_supplier_promo_type t ON t.id = s.id_supplier_promo_type
WHERE s.id_product = 2805 AND s.id_product_attribute IN (3471, 0)
  AND NOW() BETWEEN s.date_from AND s.date_to;
```

## Las ventas, cómo se calculan

Al consultar, de los pedidos de PrestaShop (no se copian a ninguna tabla: una lista subida tarde
cuenta desde el primer día, y un pedido cancelado deja de contar solo).

- **Pedidos**: los del periodo (`orders.date_add`) con algún artículo del acuerdo. Por defecto los
  pagados (`orders.valid = 1`); con «incluirlos», también los pendientes de pago. Nunca los de los
  estados de Ajustes.
- **Valor sin IVA de una línea** = lo que PrestaShop cobró por ella sin IVA (`unit_price_tax_excl` ×
  unidades) menos los vales de la cesta que le tocan:
  - los de kpycoupons («este producto»): el del regalo (`KPYRG`) a la línea regalada, que queda a 0;
    el del Special Price recuperado (`KPYSP`) a la línea que llevaba el regalo o el cupo; el del cupón
    con cupo (`KPYCP`) a la del cupo (lo apunta `kpycoupons` en `kpy_coupon_regalo_pedido` y
    `kpy_coupon_cupo_pedido`); la 2ª unidad (`KPY2U`) a las pagadas de su producto;
  - los cupones de las promociones («productos seleccionados»): a las líneas que cumplen sus
    restricciones (`cart_rule_product_rule*`: combinaciones, productos, marcas, categorías,
    atributos, proveedores; grupos «todas» o «una»), como PrestaShop;
  - el del más barato, a la más barata de las que cumplen; uno «sin productos rebajados», a las
    líneas sin precio específico;
  - uno de todo el pedido (un código de cliente), a todas en proporción a lo que valen; después de
    los de producto, para que un regalo (ya a 0) no se lleve parte;
  - los de solo portes no cuentan; uno de portes más importe, solo el importe.
- **Unidades**: las pedidas menos las devueltas o reembolsadas (con su parte de vales). «De regalo»:
  las que se dieron gratis con una promoción de regalo.
- **Lo que no se puede saber** (una regla de carrito borrada) va a todas las líneas en proporción.
- **Aproximación conocida**: si un pedido lleva dos formatos del mismo producto y un vale de la cesta
  sin registro de a cuál fue, se reparte entre los dos en proporción. El total del producto es
  exacto; el reparto entre formatos, aproximado.

## Hooks

Todos son de `kpycoupons`, que los define, los crea al instalar y documenta sus parámetros (su
contrato: `classes/KpcContrato.php` y su README). Tres los escucha este módulo; el de consulta lo
dispara este módulo y lo contesta `kpycoupons`.

| Hook | Para qué |
|---|---|
| `displayKpyCouponsFormulario` (`version`, `id`, `valores`, `duplicar`, `paso`, `url_editar`, `url_ver`) | Pinta el paso «Acuerdo con el proveedor» en el formulario de cada promoción. El número del paso llega en `paso` (`kpycoupons` ejecuta el hook módulo a módulo, en el orden de Diseño → Posiciones, y numera 6, 7…; el número solo se gasta si el paso pinta algo); si no llega, el 6. Los enlaces `url_editar`/`url_ver` a la pantalla de Promociones no se usan: el paso enlaza a la pantalla de acuerdos, que es la suya |
| `actionKpyCouponsGuardar` (`version`, `id`, `valores`, `promocion`: la fila pública) | Al guardar la promoción: guarda **lo marcado en el paso** (tipo y %) en `kpy_supplier_promo_coupon` y cuadra el acuerdo en el momento (`KsAcuerdos::ponerAlDia()`, con el hook de consulta de abajo). Si `kpycoupons` no contesta, lo marcado queda guardado, el acuerdo no se toca y se apunta en el registro |
| `actionKpyCouponsReparto` (`version`, `id_shop`, `items`, `avisos`, `alcance`, `promociones`, `guardada`) | En cada reparto, por promoción en marcha (activa, sin terminar, con cupo): `items` (lo que le toca de verdad), `alcance` (`entran` y `especial`: los artículos de su paso 2 tal como los calcula `kpycoupons`, clave = id de la combinación o −id_product sin combinaciones, antes de quitar agotados y choques), `promociones` (su **fila pública**; de ella se usan `fecha_desde` y `fecha_hasta`) y `guardada` (la recién guardada en la pantalla; 0 en los repartos por fecha o por consola). Las promociones con acuerdo ponen al día sus fechas, su tipo y % y dan el acuerdo a los artículos nuevos; la recién guardada hace además el espejo (ya hecho al guardar: aquí se repite, es idempotente y hace de red). Una con acuerdo que no viene en `alcance` (`kpycoupons` no pudo calcularla) no se toca y se apunta en el registro |
| `actionKpyCouponsAlcance` (consulta: `id`, `id_shop` → `{version, entran, especial, promocion}` o null) | Lo dispara este módulo (`KsAcuerdos::alcanceDeKpycoupons()`, con `Hook::exec(..., null, true)` y la respuesta de `kpycoupons`) cuando necesita los artículos de una promoción fuera del reparto: al guardar la promoción y con el botón «Poner al día con la promoción». Sin respuesta (o sin `entran`), no se toca nada |

Configuración: `KPS_ESTADOS_FUERA` (los estados que no cuentan; vacía = cancelado, error, reembolsado)
y `KPS_AVISOS_PASO` (el aviso de un % que no se entendió al guardar una promoción, que el paso
enseña una vez).

## Lo que lee de kpycoupons

Ninguna llamada a sus clases ni a su instancia (`Module::getInstanceByName`), ningún `require` de
sus ficheros, ninguna escritura en sus tablas. Lo que sí: lecturas de tres tablas suyas, solo para
administración y declaradas aquí (columnas que se usan; se dan por estables; si `kpycoupons` las
cambia, se cambia aquí):

| Tabla | Columnas | Para qué | Dónde |
|---|---|---|---|
| `kpy_coupon` | `id_kpy_coupon`, `codigo`, `nombre`, `tipo`, `activo`, `fecha_desde`, `fecha_hasta` | Las promociones que se pueden enlazar al subir una lista y sus fechas (que mandan sobre las del formulario); el nombre y el estado de la promoción de cada lote; el nombre de la hoja Excel | `KsAcuerdos::promosParaElegir()`, `KsAcuerdos::lotes()`, `AdminKpySupplyController::leerFormulario()` y `descargar()` |
| `kpy_coupon_regalo_pedido` | `id_order`, `disparador`, `r_ipa`, `cantidad` | Qué unidades de un pedido fueron de regalo (a 0 €) y qué formato las disparó, para llevar los vales `KPYRG`/`KPYSP` a su línea y contar «unidades de regalo» | `KsVentas` (solo si la tabla existe) |
| `kpy_coupon_cupo_pedido` | `id_order`, `clave` | Qué formatos de un pedido llevaban unidades de un cupo, para llevar los vales `KPYCP`/`KPYSP` a su línea | `KsVentas` (solo si la tabla existe) |

Y los contratos de nombres que da `kpycoupons`:

- de su contrato (`KpcContrato`, versión 1): la **fila pública** de una promoción (en `promociones`
  del reparto y en `promocion` del hook de consulta), de la que se usan `fecha_desde` y `fecha_hasta`
  (`string|null`); y el alcance (`entran`, `especial`: clave ⇒ `id_product`);
- los **prefijos de los vales** en `cart_rule.code`: `KPYRG` (regalo), `KPYSP` (Special Price
  recuperado) y `KPYCP` (cupón con cupo), para saber a qué línea del pedido va cada uno;
- la pestaña `AdminKpyPromociones` con `&editar=ID` (los enlaces a la promoción desde la pantalla
  propia; en el paso del formulario llegan hechos por el hook, `url_editar`/`url_ver`) y las clases
  CSS `kpc-paso`, `kpc-paso__titulo`, `kpc-num`, `kpc-opcional`, `kpc-ayuda`, `kpc-radios`,
  `kpc-check` y `kpc-nota` del formulario de Promociones, que el paso usa para parecer un paso más.

El lector de listas (`classes/KsLectura.php`) es una **copia** de `KpcLista` de `kpycoupons` (con lo
poco que aquella pedía a `KpcAlcance` y `KpcFormatos` para saber qué formato es pack de cuál), y
`KsAcuerdos::fechaValida()` lo es de `KpcCupon::fechaValida()`: código puro que los dos módulos
necesitan se copia, no se comparte. No hay un «lector generado» ni nada que mantenga las dos copias
iguales por programa: `KsLectura` lleva más que `KpcLista` (su `indice()` trae lo de packs y
apagados), así que basta con que las dos copias compartan pruebas (`tests/lectura.php` es la batería
de `kpycoupons/tests/lista.php`) y con que la prueba de servidor compare los dos índices del catálogo
real. Lo que lee `KsLectura::indice()` son tablas del core y de Julián (`product`, `product_shop`,
`product_attribute`, `product_supplier`, `kpy_product_attribute`, `kpy_packs`), no de `kpycoupons`.

## Instalar, desinstalar, quitar

```bash
php bin/console prestashop:module install kpysupply     # después de kpycoupons 3.0.0
php bin/console prestashop:module uninstall kpysupply   # quita pestaña, hooks y ajustes; las tablas se quedan
php bin/console prestashop:module upgrade kpysupply     # de una versión anterior (sin ficheros de upgrade: solo sube la versión)
```

Reinstalar encima de las tablas con datos los respeta (probado). Para quitar las tablas de verdad,
el SQL de `deshacer/` del paquete (antes, sacar el Excel de lo que haga falta).

## Probar

- Puras, sin tienda (`php -n -d extension=mbstring -d extension=zip`), en `tests/`: `lectura.php` (el
  lector de listas: la misma batería que `kpycoupons/tests/lista.php`), `lista.php` (la lista del
  proveedor, las columnas, los solapes, los recortes), `ventas.php` (a qué líneas toca cada vale y el
  reparto) y `excel.php` (el .xlsx y el CSV).
- De servidor, **solo en pruebas**: `modulos/kpycoupons/tests/servidor/acuerdos-pruebas.php` (vive en
  la carpeta de `kpycoupons` porque crea promociones de prueba con las clases de `kpycoupons`; en la
  de este módulo no hay ni un `new KpcCupon`, tampoco en sus pruebas). Escribe acuerdos de prueba y
  una promoción, lanza el reparto de `kpycoupons` de verdad y lo borra todo al final:
  `cat modules/kpycoupons/tests/servidor/acuerdos-pruebas.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php --`.
  Cubre los tres caminos (guardar una promoción apagada con acuerdo → espejo por el hook de consulta;
  el botón «Poner al día» → `kpsSincronizarPromo`; el reparto → lo que trae) y la respuesta nula del
  hook de consulta (no toca nada).

`tests/` no va en los paquetes para producción.

## Versiones

- 1.0.0 (20/10/2026): la primera.
- 1.0.1 (20/10/2026): el paso del formulario de Promociones, más claro: el % de cada tipo sale solo
  con su tipo marcado (CSS `:has()`), en su línea bajo la opción. Solo plantilla (`paso.tpl`).
- 1.1.0 (23/10/2026): **sin llamadas a `kpycoupons`** (regla de Julián), sobre su contrato 3.0.0.
  Lector de listas propio (`KsLectura`, copia de `KpcLista`) y `fechaValida` propia; los artículos de
  una promoción los da `kpycoupons`: en el reparto, con lo que trae (`alcance`, `promociones`,
  `guardada`), y al guardar la promoción o con el botón «Poner al día con la promoción», por su hook de
  consulta `actionKpyCouponsAlcance` (si no contesta, no se toca nada y se dice); el número del paso
  llega en `paso`; la versión de `kpycoupons` se lee sin cargar el módulo; fuera
  `Kpysupply::kpycoupons()` y `numeroDelPaso()`. Pide `kpycoupons` 3.0.0. Sin tablas nuevas ni
  fichero de `upgrade`.
