# kpycoupons · Promociones de Kompy

Todas las promociones de la tienda en una sola pantalla (**Catálogo → Kompy · Promociones**): el
**Special Price** (el precio rebajado en la ficha), el **cupón con código**, la **2ª unidad al %**
y, desde la 2.0.0, el **regalo con la compra** (compras el saco grande y entra el pequeño a 0 €).
Se dicen una vez (a qué productos, cuándo, cómo llegan al cliente) y el módulo deja puesto todo lo
demás: precios, reglas de carrito, etiquetas de los listados y ficheros de Google Merchant. Desde la
2.3.0 cualquiera de ellas puede ir **por cantidad** (un cupo de unidades que se cuenta solo y la
deja agotada; ver «Por cantidad: el cupo»), desde la 2.4.0 **por artículo** (la lista pegada trae
las unidades de cada EAN: lo de una liquidación), y el Special Price puede llamarse «Liquidación»,
«Outlet» o «Bajada de precio» (las dos primeras van al cajón «Outlet y liquidación» de la landing,
sin banner propio). **Desde la 3.0.0 (23/10/2026) la ficha la pinta este módulo** en los huecos del
tema de Julián (ver «La ficha, en los huecos del tema») y **no depende de ningún otro módulo ni
llama a ninguno**: lo que otros necesitan saber les llega por hooks propios con datos curados (ver
«Contratos públicos»). `kpyproductbuy`, que pintaba la ficha hasta la 2.4, se retira.

## Los cuatro tipos

| Tipo | Qué ve el cliente | Cómo se hace |
|---|---|---|
| **Special Price** | El precio ya rebajado en la ficha y los listados, el habitual tachado, «hasta el…» y la etiqueta «Special Price» —o «Liquidación» o «Bajada de precio», según cómo se le llame— (sin ninguna otra de precio) | Un precio específico de PrestaShop con fechas, como los carga Julián (ver «Special Price») |
| **Cupón con código** | Un bloque en la ficha para aplicarlo de un toque; el descuento en la cesta | Una regla de carrito por cupón, restringida a sus artículos exactos |
| **2ª unidad al X %** | «2ª ud. −30 %» en la ficha; en la cesta, al llevar dos, la segunda al % | Calculada en cada cesta: media unidad por cada par (4 uds = 2 con descuento) |
| **Regalo con la compra** | «+ 3 kg gratis» en la ficha y «Te llevas de regalo…» con su foto; en la cesta, el regalo en su línea a 0 € («1 Gift») y un vale «Regalo: 1 × …» | El regalo es una línea normal (con su EAN y su stock) y una regla de carrito de esa cesta descuenta lo que vale (ver «Regalo con la compra») |

Reglas de Kompy que se cumplen solas:

- **Un artículo, una promoción de precio.** Un formato con Special Price no lleva cupón ni 2ª unidad
  encima (salvo que el cupón desmarque «Respetar el Special Price»). Entre cupón y 2ª unidad manda la
  que más descuenta de verdad. Entre dos Special Price, el que ya estaba. **El regalo no es de
  precio: se apila con todo** (un saco con Special Price y cupón regala igual); entre dos regalos
  sobre el mismo artículo manda el más antiguo.
- **Los packs entran solos**: si entra «Saco de 15 Kg», entra «Pack 2 x Saco de 15 Kg» con el mismo
  descuento («Pack 2 x … + 3 kg gratis» es otro producto y no hereda). La 2ª unidad no entra en
  packs: el pack ya es el descuento por cantidad. Qué es pack de qué sale de la tabla de packs de
  Kompy (`kpy_packs`) y, si no está ahí, del nombre (`KpcFormatos`). El **nivel** es cuántos sueltos
  lleva: en Schesir la caja de 12 latas es lo suelto (no hay lata suelta), la de 24 es «pack de 2»
  y la de 48, «pack de 4». Hasta la 1.5 la caja de 12 se tomaba por pack y la 2ª unidad no le llegaba.
- **Nunca entra lo que no se vende**: productos apagados, ocultos (visibilidad «ninguna», como los
  antiparasitarios que se retiran sin borrar) o que no se pueden pedir, y **formatos apagados**
  (`kpy_product_attribute.active = 0`, los que la ficha no enseña: en pruebas son 6.094). Si llegan
  en una lista, se dice cuántos.
- **Los formatos OUTLET se quedan fuera** (ya van rebajados), salvo que se desmarque.

## Regalo con la compra

**Desde la 2.1.0 (16/10/2026, tarde) el cliente elige.** En la ficha sale una tarjeta más al lado
de la de su formato, como un saco Jirafa: «12 kg + 3 kg gratis» (franja «Kilos gratis») o, si el
regalo es otra cosa, «12 kg + Regalo», «+ Snacks», «+ Pack latas» (franja «Con regalo»; el texto se
decide solo o se escribe en «Cómo se llama la tarjeta»). La tarjeta va al **precio habitual** del
saco, sin el Special Price, con el tachado contra el PVPR de los dos y el €/kg con los kilos del
regalo. La tarjeta de siempre sigue con su Special Price y sin regalo. Con la de regalo, la ficha
manda `kpy_regalo=1` al añadir; la línea queda apuntada en `kpy_coupon_regalo_eleccion` y el saco
lleva un precio de esa cesta (`specific_price.id_cart`, con su id en la elección: nunca se toca otro)
con la rebaja de siempre (`old_discount`). Reglas: una línea va entera con regalo o entera sin él
(la ficha avisa si se mezclan; **desde la 2.2.0, por unidades**: ver abajo); la papelera del vale
«Regalo» de la cesta quita la versión con
regalo y el saco vuelve a su Special Price; sin regalo (agotado, tope) no se cobra el habitual; si
la promoción termina, la elección se va; «pedir de nuevo» no cobra las unidades que fueron regalo
(hook `actionDuplicateCartData`). Un artículo lleva una sola promoción de cesta: un cupón o una 2ª
unidad se quedan el artículo y el regalo lo suelta. A Google no va el regalo en artículos con Special
Price (allí sale rebajado y la tarjeta con regalo va a otro precio). El bloque «Te llevas de regalo»
con foto solo sale si el regalo no son kilos. En la landing va como «Kilos gratis»
(`boske-kilos-gratis`, «Boske: kilos GRATIS con tu saco», con los tamaños) o como «Regalos».
En la cesta, cada línea lo dice (hook `displayCartExtraProductActions`, primero, encima del «Recíbelo
entre…»; el tema esconde formato y precio en la línea de regalo): en la del regalo, «Regalo · Saco de
3 Kg · 0,00 € (18,89 €) · Con tu Saco de 12 Kg»; en la del saco, «Kilos gratis · Lleva de regalo 1 ×
Saco de 3 Kg · A precio habitual por llevar el regalo».
Probado el 16/10 con los pedidos #866296 y #866297 y el recorrido de un cliente en Chrome (PC y móvil).

**Desde la 2.2.0 (16/10/2026, noche), por unidades.** Rafa añadió a una cesta con «12 kg + 3 kg
gratis» la tarjeta «12 kg» con Special Price, y la línea entera pasó a sin regalo: el regalo
desapareció. Ahora cada tarjeta añade lo suyo: `kpy_coupon_regalo_eleccion.unidades` dice cuántas
unidades de la línea van con regalo. La ficha suma unidades con regalo (`kpy_regalo=1`) o sin él
(`kpy_regalo=0`): se mira cuánto pagaba la línea antes del cambio (hook
`actionCartUpdateQuantityBefore`) y cuánto después (`actionCartSave`), así cuenta lo que de verdad
entró. PrestaShop solo deja un precio por línea: la línea va al precio de la versión con regalo (el
de siempre) y las unidades de la tarjeta de siempre recuperan su Special Price con un vale de esa
cesta, **«Special Price: 1 × … · Saco de 12 Kg»** (`KPYSP-{cesta}-{producto}-{firma}`, por la
diferencia exacta, sin papelera, en crema). Con «uno por pedido», una unidad va con regalo y las
demás a su Special Price. En la cesta y en el pago, cada línea sale **partida en filas** (hook
`actionPresentCart`, `KpcLineas`, como hace PrestaShop con sus regalos): «Saco de 12 Kg + 3 kg
gratis», «Saco de 12 Kg» (Special Price), los 3 kg que paga el cliente y el 3 kg de regalo, cada fila
con su cantidad y su «+», «−» y papelera, que cambian solo esa parte (llevan `kpy_regalo`; la
papelera de una parte baja sus unidades en vez de borrar la línea). Cada regalo va **justo debajo
de su saco**, sea lo que sea (`KpcLineas::ordenar`, una fila por saco que lo regala), en un bloque
violeta pegado a él y con la etiqueta «1 Regalo(s)» del tema en píldora; en el resumen del pago,
igual y a «0,00 €» con lo que vale tachado. Debajo del nombre, cada fila
dice su promoción con los colores de la ficha (`linea-promo.tpl`): Regalo, Kilos gratis, Special
Price («Ahorras 7,10 € más · Precio habitual 70,99 €»), 2ª unidad («Te ahorras 8,40 €») o cupón. Y el
empujón «Añade 1 ud. más…» ya no cuenta las unidades regaladas. Probado con la ficha del Boske
Clinical Diet Hypoallergenic y sus tres promociones de pruebas (Special Price, regalo y 2ª ud.
−40 % en el 3 kg), el pedido #866298 (línea mezclada: dos vales) y el recorrido en Chrome (PC y
móvil). Pendiente: durante una campaña (Black Friday), las píldoras de las filas van con sus colores
de siempre (los vales del resumen sí toman los de la campaña).

«Compras el saco de 12 kg y te llevas el de 3 kg». Es la promoción de Boske (12 + 3, 15 + 3, 10 +
2,5 en gato, 8 + 2 en mini), y vale para cualquier marca y cualquier regalo que exista como producto:
una caja de latas, un snack, una carda. El regalo **no** es el saco «+ 3 kg gratis» del fabricante
con su EAN propio: es el producto de siempre, el que ya está en el almacén.

**Cómo se registra** (lo que importa): igual que el regalo de PrestaShop. El regalo es **una línea
normal** de la cesta y del pedido, con su referencia, su EAN, su precio y su stock, y una **regla de
carrito propia de esa cesta** (`KPYRG-{cesta}-{producto}-{firma}`) descuenta exactamente lo que vale
(a su precio de hoy, Special Price incluido; con un cupón de Kompy puesto que le llegue, el precio ya
rebajado por el cupón, así el neto es 0,00 y no sale negativo). El pedido suma 0 € por él, el
almacén o el distribuidor lo preparan como cualquier línea (la línea del pedido dice **«(regalo)»**,
y el email también), la factura cuadra (línea + descuento) y el stock baja. Lo regalado queda en
`kpy_coupon_regalo_pedido` con su precio y su coste: el **sell-out** para la marca y el tope de la
campaña. Como todo pasa por `order_detail`, los conectores de Distrivet, Neftys y la tienda lo
mandan a su almacén sin tocarlos: el regalo conviene que sea del mismo proveedor que lo que se compra
(la pantalla avisa si es de otra marca).

**Qué se regala** (se decide al guardar y se deja escrito en `kpy_coupon_regalo`):

- **El formato pequeño de la misma ficha** (lo normal): el más ligero del mismo envase (un saco
  regala un saco, no una lata), suelto, a la venta, sin OUTLET y sin «gratis» en el nombre. Un pack
  de 2 × 12 kg apunta al pequeño de su saco. Un producto sin formato pequeño regala el **de
  reserva**, y sin reserva se queda fuera (la pantalla dice cuántos y cuáles).
- **El mismo artículo para todos**: cualquier artículo de la tienda, buscado por nombre, EAN,
  referencia o id (solo lo que se vende).
- **A mano, por fila**: «Cambiar regalo» en los productos de la promoción (`regalo_parejas`, JSON
  clave → {r, s}); manda sobre el modo.
- **Reserva** (opcional): si el regalo está agotado entra la reserva; si tampoco, la cesta dice «El
  regalo (…) está agotado: tu compra sigue igual» y la compra sigue.
- **Cuántos**: uno por cada unidad comprada (el pack de 2 regala dos, si «los packs también
  regalan»; si no, solo disparan los sueltos) o uno por pedido. **Tope** de campaña opcional (los que
  pone el fabricante): al llegar, la promo se para sola y la cesta lo dice.

**La cesta, en todas las direcciones** (`KpcRegalo::plan`, probado sin tienda en `tests/regalo.php`
y con ella el 16/10/2026): en cada guardado de la cesta se cuentan los disparadores **sin contar las
unidades regaladas** (`kpy_coupon_regalo_cesta` dice qué parte de cada línea es regalo), y la línea
del regalo se deja con la cantidad que toca. Añadir un saco mete el regalo; dos sacos, dos; bajar a
uno, uno; quitar el saco quita el regalo (y la regla se borra); borrar la línea del regalo lo vuelve
a meter mientras esté el saco (la línea de regalo entero no lleva papelera ni cantidad: «1 Gift»);
comprar además una unidad suelta del mismo regalo la paga y se le regala otra (la línea va mezclada:
2 uds, 1 de regalo); lo regalado no dispara otra promoción ni cuenta para la 2ª unidad; si la tienda
no deja añadirlo (se agotó entre medias) se avisa y la compra sigue. Orden de cálculo en cada guardado:
regalos → 2ª unidad (solo unidades pagadas) → cupón → la regla de los regalos, la última (prioridad 90,
sobre lo que ya cuesta).

**Dónde se ve**: la ficha (3.0.0, este módulo: la tarjeta «12 kg + 3 kg gratis» en la fila de
formatos, montada por `ficha.js`, y con ella elegida la pastilla «Kilos gratis», «Incluye 3 kg gratis
(17,99 €), a 0 € en la cesta», hasta cuándo y la foto si el regalo es otra cosa),
los listados y Doofinder (etiqueta «+ 3 kg gratis», `kpy_promo_tipo = regalo`), la
cesta (línea «Gift», vale «Regalo: 1 × …» en violeta, sin papelera, y los avisos de agotado o tope),
la confirmación y el historial del pedido (la línea como regalo), Google Merchant (promoción
`free_gift` con lo que se regala) y la landing de promociones (`/promociones/regalos` y «Marca:
Regalo», `kpypromolanding`). Nada que pulsar: entra solo al añadir el artículo.

**Límites sabidos**: el regalo tiene que existir como producto a la venta; si un producto no tiene
formato pequeño y no hay reserva, no regala (se dice en la pantalla); un cliente que quiera comprar
el mismo artículo que le regalan lo añade desde su ficha (la línea pasa a mezclada y recupera la
cantidad); el descuento de la regla se reparte entre las líneas de ese producto como hace PrestaShop
(en una línea mezclada con cupón puede bailar un céntimo).

## Por cantidad: el cupo (2.3.0, 19/10/2026)

Cualquier promoción puede llevar un **cupo de unidades** además de las fechas (paso 3 de la
pantalla, «Cupo de unidades»; 0 = sin límite). Qué es una unidad según el tipo (`KpcCupo::UNIDAD`):

| Tipo | Una unidad del cupo es… |
|---|---|
| Special Price | una unidad vendida al precio rebajado |
| Cupón | una unidad con el descuento del cupón |
| 2ª unidad | una segunda unidad rebajada (4 uds. = 2 del cupo) |
| Regalo | un regalo entregado (es el «tope» de antes: `regalo_tope` sigue al cupo) |

**El contador.** `quedan = cupo − vendidas − en otras cestas` (`KpcCupo::estado`). *Vendidas* son
las de pedidos (`kpy_coupon_cupo_pedido`; para los regalos, `kpy_coupon_regalo_pedido`) que no
estén cancelados ni en error de pago. *En otras cestas* son las reservas vivas
(`kpy_coupon_cupo_cesta`: cestas tocadas hace menos de 60 minutos, `KpcCupo::MINUTOS_RESERVA`, que
no sean ya un pedido). Cada guardado de la cesta rehace sus reservas; la propia cesta nunca cuenta
en su contra. Las unidades se dan **por orden de la cesta** (`KpcCupo::repartir`): lo que cabe,
cabe; lo que no, va sin promoción.

**Cuando no cabe todo en una línea.** PrestaShop solo deja un precio por línea, así que:

- **Special Price**: la línea va al precio habitual (un `specific_price` de esa cesta,
  `KpcPrecioCesta`, con la rebaja de siempre) y un vale `KPYSP-…` devuelve la rebaja de las que
  caben, con el nombre de la promoción («Liquidación: 2 × Boske … 12 kg»). La fila de la cesta se
  parte (`KpcLineas`, parte `tope`) y lo dice: «Liquidación: solo quedaban 2 ud. en promoción; las
  otras 1 van a precio habitual» (`KpcResumen::avisosDeCupo`).
- **Cupón**: la regla global del cupón no entra en esa cesta; un vale por producto
  `KPYCP-{promoción}-{cesta}-{producto}-{firma}` descuenta el % de las que caben («TESTCUPO10
  (-10 %): 2 × …»). La papelera del vale olvida el cupón, como siempre (`olvidarPorVale`).
- **2ª unidad**: se rebajan tantas segundas unidades como quepan (`KpcSegunda::reparto`, con
  `por_linea`).
- **Regalo**: tantos regalos como quepan; el resto de unidades, sin regalo y sin pagar de más.

Todo eso lo decide `KpcCupoCesta` dentro de `KpcCarrito::sincronizar()`, con cerrojo
(`GET_LOCK('kpc_cupo', 3)`, `KpcCupo::cerrojo`) para que dos cestas no se repartan las mismas
últimas unidades.

**Al pedir.** Justo antes de crear el pedido (`actionValidateOrderBefore`) la cesta se repasa una
vez más con las reservas al día: si estuvo más de una hora parada y otros se llevaron las últimas,
el pedido sale con los precios de ahora (si el importe ya no cuadra con lo que calculó el módulo de
pago, PrestaShop deja el pedido en «error de pago», como con cualquier cambio de última hora, y ese
pedido no cuenta). Después se vacía la caché estática de la cesta (`Cart::resetStaticCache()`:
PrestaShop guarda la lista de paquetes y no la rehace al cambiar las líneas; sin esto el #866299 de
pruebas salió con 3 unidades teniendo 5). Al validar (`actionValidateOrder`), las reservas de la
cesta pasan a vendidas (`KpcCupo::alPedido`); si con ese pedido `vendidas ≥ cupo`, la promoción
queda **agotada** (`cupo_agotado` con fecha) y se apunta el próximo reparto.

**Agotada = fuera de todas partes.** Una promoción agotada no está en vigor (`KpcCupo::sqlVivo`,
dentro de `KpcCupon::enVigor` y del reparto): en la siguiente visita el reparto le quita sus Special
Price, etiquetas, landing y Google, igual que cuando acaba por fecha; las cestas que aún la llevaran
vuelven al precio de siempre al repasarse (precio de cesta y vales fuera, `valesMuertos`) y no avisan
de nada; la ficha deja de nombrarla. **No se reabre sola** (una cancelación no la revive): desde la
pantalla con «Reabrir» (`KpcCupo::reabrir`), o subiendo el cupo por encima de lo vendido. Se queda
en la lista con su contador.

**Lo que se ve.**

- Ficha (3.0.0, `KpcFicha`): el nombre del Special Price en el chip de la tarjeta y en la etiqueta
  de la foto, «Quedan 8 unidades en Liquidación» / «¡Últimas 2…!» bajo el precio si la promoción lo
  pide (`cupo_ficha`), y **el precio habitual, sin amarillo**, cuando para esa cesta no queda nada
  («Se han agotado las unidades en Liquidación: a precio habitual»). El cupón, la 2ª unidad y el
  regalo dicen también cuántas quedan.
- Listados y Doofinder (`KpcFlags::etiquetaEspecial`): la etiqueta se llama como la promoción, en
  rojo (`kpc-liquidacion` #B3261E, `kpc-bajada` #C23A2C).
- Cesta: la fila partida, los avisos de `KpcResumen` y el vale del Special Price dibujado como tal;
  el vale `KPYCP` como cupón.
- Pantalla: en la lista, «Agotada» con fecha, «cupo N · vendidas V · en cestas R · quedan Q» y
  «Reabrir»; en el formulario, el paso 3 «Cupo de unidades» con «enseñar en la ficha cuántas
  quedan», y en el Special Price «¿Cómo se llama en la tienda?» (`etiqueta` = '' | `liquidacion` |
  `bajada`; nombres en `KpcCupo::ETIQUETAS`; códigos por defecto `LIQ…`, `BAJADA…`).
- Landing (`kpypromolanding` 1.2.1): agotada = terminada.

**Cuándo cuesta algo.** Si no hay ninguna promoción con cupo encendida (`KpcCupo::hayCupos`, una
consulta por petición), nada de esto se ejecuta: ni cerrojo ni repaso antes del pedido; las
reservas y los precios de cesta huérfanos se barren con `KpcLimpieza` (`KpcCupo::limpiar`,
`KpcPrecioCesta::limpiar`).

### Unidades por artículo (2.4.0, 19/10/2026)

Para una liquidación (Rafa: «un listado de EAN, referencias, con las cantidades; multimarca; todos
al mismo sitio»), la lista pegada trae, al lado de cada EAN, cuántas unidades se venden con la
promoción: la casilla **«Las unidades van en la lista, artículo a artículo»** (`kpy_coupon.cupo_lista`)
y dos columnas, EAN y unidades (con un % por artículo: EAN, %, unidades; con cabeceras manda la
cabecera «Unidades»; «8436000000015 12» en una sola columna también vale; `KpcLista::buscar(…,
$conUnidades)`, `leerUnidades`, `columnaDeUnidades`). Se guardan en `kpy_coupon_scope.unidades` y
se cuentan **artículo a artículo** (`KpcCupo::limites`, `estadoArticulos`, `quedanPara($promo,
$cesta, $clave)`, `repartirPara`, la regla pura `repartirConLimites`): lo que queda de un artículo es
lo menor entre lo suyo y el cupo total de la promoción, si lo hay (el de arriba es opcional: 0 =
solo las unidades de la lista).

Cada artículo que vende las suyas queda **agotado** (`kpy_coupon_scope.agotado`, en
`KpcCupo::alPedido`) y el siguiente reparto lo deja fuera (su precio vuelve, su etiqueta se va, la
ficha deja de nombrarlo, la landing lo quita) mientras la promoción sigue con los demás
(`KpcCupo::agotadasDe` en `KpcReparto`); cuando no le queda ninguno, la promoción entera queda
agotada (`comprobarAgotada`). Volver a guardar la lista **no** reabre un artículo agotado, salvo que
le dé más unidades que las vendidas (`KpcCupon::guardarAmbitos`); «Reabrir» los reabre todos; 0
unidades en la lista es dejar ese artículo fuera; el mismo EAN dos veces (dos lotes) suma. Un OUTLET
pegado en la lista con su propio EAN entra aunque esté marcada «OUTLET fuera» (se ha pedido a
propósito; `KpcElegibles`). En la pantalla: la columna «Unidades» (las suyas, vendidas, quedan), la
fila «Agotado», «N sin unas unidades que se entiendan» y, en la lista de promociones, «Por artículo:
48 uds. en 5 artículos: 12 vendidas · quedan 36 · 1 agotado». En la ficha, el cupón y la 2ª unidad
dicen lo que queda **del formato elegido** (`quedan_por`, `KpcFicha`).

### «Outlet» y el cajón de la landing (2.4.0)

Además de «Liquidación» y «Bajada de precio», un Special Price puede llamarse **«Outlet»** (etiqueta
gris oscuro, `kpc-outlet` #3A3A3A; código por defecto `OUTLET-…`). Liquidación y Outlet
(`KpcCupo::CAJON`, `esCajon`) **no son promociones con banner**: `kpypromolanding` 1.3.0 las manda,
con todas las marcas juntas, al cajón **«Outlet y liquidación»** (la categoría permanente `outlet`,
`/promociones/outlet`, con el filtro perros/gatos y un botón a la categoría de PrestaShop, que lleva
los filtros de marca, precio y mascota de la tienda), sin categoría ni banner por marca y sin
bloque en la landing de marca. «Bajada de precio» sigue siendo una promoción normal, con su banner.

## La pantalla

Se entra a lo de siempre, en este orden:

- **Promociones**, arriba y **plegada**: el resumen se ve siempre («3 en marcha · 1 programada») y el
  ojo la despliega. En cada una, **👁 Productos** despliega sus productos debajo de su fila, sin tocar
  el formulario ni recargar la página (se piden por `…&ajax=1&action=productos&id=N`, con las
  comprobaciones de acceso del back office; si fallara, el enlace recarga con `&ver=N` y salen
  igual). Desde ahí, «Quitar productos o cambiarla» la abre en el formulario.
- **Promoción nueva**, vacía, para hacer la siguiente paso a paso.
- **Google Merchant**, abajo y plegado: se configura una vez.

Al **guardar** se vuelve a la lista, abierta, con la recién guardada marcada («En marcha» o
«Programada: empieza el 03/10 a las 00:00») y sus productos a la vista; el formulario, vacío para la
siguiente. Apagar, encender, borrar y «Actualizar ahora» también vuelven a una dirección limpia:
recargar la página no los repite (hasta la 1.5, recargar tras «Apagar» la volvía a encender).

Lo que se enseña al elegir una marca (gamas, formatos y packs de todo el catálogo) se guarda 10
minutos en la caché de PrestaShop: la pantalla se pinta en 0,1 s en vez de 0,36 s.

## Crear una promoción

Paso a paso, y en cada paso **solo lo de lo que se ha elegido**: al abrirla solo se ve el paso 1; al
elegir el tipo, lo de ese tipo (el código solo en el cupón, los modos solo en el Special Price…); en
el paso 2, primero **cómo** se eligen los productos, y luego solo lo de esa manera; los pasos 3 a 5,
después. Lo decide el CSS (`:has()`), no el JS: está bien desde el primer momento aunque el JS tarde
o falle.

1. **Qué promoción**: el tipo y el %. En un **Special Price**, además, cómo se rebaja:
   - **el mismo % para todos**;
   - **cada artículo con su %** (va en la lista, al lado del EAN);
   - **cada artículo con su precio final, IVA incluido** (también en la lista: el PVPR del proveedor).
   Los que no traen el suyo llevan el % general, si lo hay (con 0, solo entran los de la lista).
   El código se sugiere solo (una marca y 15 % → `ROYALCANIN15`), **20 caracteres como mucho**
   (Google no admite más); la 2ª unidad y el Special Price llevan uno interno que el cliente no ve.
2. **A qué productos**: primero **cómo**, con dos tarjetas como las del paso 1:
   - **Por marca**: la **marca**, escribiendo; al elegirla salen, con cuántos productos a la venta
     tiene cada cosa:
     - sus **gamas**: el árbol de categorías de su página de marca (`kpy_manufacturer`), con buscador.
       Una gama afina **solo su marca**: «Royal Canin (Razas pienso gato) + Farmina» es Royal Canin de
       razas gato y toda Farmina. Sin marcar ninguna, toda la marca;
     - sus **formatos**, por envase («Sacos: 1,5 kg · 2 kg · 12 kg…», «Latas: 70 g…»). El formato es
       el del suelto y vale para sus packs: «saco 12 kg» es el saco y el «Pack 2 x 12 Kg». Sin
       marcar ninguno, todos;
   - **Con una lista**: la del proveedor, pegada o su Excel/CSV (abajo). Con un % o un precio por
     artículo es siempre con la lista: «Por marca» ni se ofrece.
   - Para las dos: **Incluir los packs** (marcado de serie; sin marcar, solo los sueltos). Por marca,
     además, **¿Solo algunos packs?** «Pack de 2», «Pack de 4»…: marcando, solo esos packs y sin los
     sueltos (Schesir, packs de 2). La 2ª unidad no lo enseña: nunca lleva packs.
   - Lo de la otra manera, aunque se hubiera rellenado antes de cambiar, no cuenta al guardar.
   - Las características (Para, Tipo, Edad) y las categorías de la tienda ya no se ofrecen (no se
     usaban); una promoción que las llevara de antes las mantiene, y la pantalla lo dice.
   - La **lista de artículos**: se pega lo del proveedor o se sube su Excel/CSV. Entiende EAN
     (con o sin ceros delante; Excel los guarda como número y valen), referencias (la nuestra, la
     del proveedor y el MPN, sin mirar espacios, guiones ni mayúsculas), ids (`7214`),
     «producto-formato» (`7214-90684`) y enlaces de la web. Con varias columnas elige sola la que
     encuentra, y la del % o el precio por su cabecera («Dto.», «PVP promo», «Oferta»…) antes que
     por contar. Vale «13», «13 %», «0,13» (porcentaje de Excel), «12,99 €», «1.234,56».
     **Lo que no encuentra lo dice uno por uno**, para reclamarlo; también los que no traen un %
     o un precio que se entienda. **El EAN del saco es del saco** aunque su pack lo comparta: el
     precio de la lista se le pone al saco, y el pack entra como pack, con el mismo descuento (hasta
     la 1.5 el pack cogía el precio de un saco);
   - **dejar fuera**: igual que la lista; lo de aquí no entra nunca.
3. **Cuándo**: desde y hasta (hora opcional), con atajos (1 semana, 15 días, 1 mes, fin de mes).
   Se puede **programar**. Un Special Price necesita fecha de fin (la ficha dice «hasta el…»); sin
   inicio, empieza en el minuto en que se guarda.
4. **Cómo llega al cliente** (cupón): en la ficha o no; **ya puesto** para nadie, para todos o
   **según de dónde llegue la visita** (Google, email, redes); y el **enlace que lo deja puesto**
   (`https://kompymascotas.com/?kpc=CODIGO`, en cualquier página). «Para todos» es lo que dice
   Julián (23/10/2026): el cupón lo ve cualquiera en la ficha de **sus** productos, venga de donde
   venga la visita (aunque «se enseña» esté desmarcado: al guardar se marca solo), y nunca en un
   producto que no entra.
5. **Google**: el cupón y la 2ª unidad se anuncian (o no) como promoción de Merchant, con su
   título; el Special Price va siempre como precio de oferta. «Bloquear PriceShape» es un aviso
   para la herramienta de precios.

**«Comprobar»** no guarda nada: enseña los productos a los que llegaría y por qué se queda fuera
cada uno. **«Guardar»** lo deja todo puesto de una vez.

## Los productos de una promoción

Salen en dos sitios: desplegados en la lista («👁 Productos», para revisar, sin «Quitar») y debajo
del formulario al comprobar o al cambiar una promoción (con «Quitar»). La tabla, en el orden que
pidió Rafa: **id, EAN, si entra, producto, formato, precio, descuento** y
el precio con la promo (en un Special Price por artículo, bajo el descuento, si es «de la lista»,
«como su suelto» o «el general»). Arriba, las cuentas: cuántos entran, cuántos ya tienen otro Special Price, cuáles
no llevan el suyo porque su precio no baja del de hoy o no tienen valor, cuáles se lleva otra
promoción que manda más, cuáles le quita esta a otra, cuántos se han quitado a mano.

- **Buscar** en la tabla por nombre, marca, formato, EAN o id.
- **Quitar** cualquiera (se apunta en «Dejar fuera» como `producto-formato`); **Deshacer** lo
  vuelve a dejar. Se aplica al **Guardar**, que está también junto a la tabla.
- Los quitados salen al final con **Volver a meter**.
- **Actualizar ahora** (en la lista) lo recalcula todo al momento: por si han entrado productos
  nuevos de una marca o se ha apagado alguno.

Hasta 2.000 filas con sus precios (1.300 precios tardan 1,1 s); las cuentas son siempre de todos.

## Special Price

Se guarda **igual que los carga Julián**, para que `kpyspecialprices`, la ficha, las etiquetas y el
fichero de Google lo lean sin saber de dónde viene:

- `specific_price`: el precio con sus fechas. Como lleva fecha de fin, PrestaShop lo prefiere a los
  de PriceShape (que no la llevan) mientras dura;
- `kpy_special_price`: el descuento de antes (`old_discount`, en tanto por uno) y el de la promo
  (`special_discount`, en %).

**El % va encima del precio de PriceShape**, como en su carga (Royal Canin, agosto: PriceShape
14,5 % + promo 12 % → 24,75 % sobre el PVPR): `1 − (1 − PriceShape) × (1 − promo)`. Así «20 %» es
un 20 % sobre el precio de hoy: Boske 15 kg, 50,99 → 40,79 €. Con precio final por artículo, se
guarda el descuento sobre el PVPR que da justo ese precio (Eukanuba 15 kg a 45,00 €, y su pack con
el mismo −14,6 %: 102,18 → 87,30 €).

Lo que pone cada promoción se apunta en `kpy_coupon_precio`, y solo eso se toca:

- **quitar un producto** (a mano o por la lista) o **apagar** la promoción devuelve el precio de
  siempre al momento; **encender** lo vuelve a poner;
- **cambiar el %, el precio o las fechas** rehace solo lo que cambia (volver a guardar sin tocar
  nada no rehace nada);
- **al terminar**, el precio vuelve solo al de siempre en el minuto de fin (PrestaShop mira las
  fechas) y en la primera visita de después se quita lo que puso (su precio y su fila de
  `kpyspecialprices`, que sin su parche seguiría diciendo «Precio especial» en la ficha);
- **borrar** la promoción quita sus precios;
- **un Special Price no pisa otro**: lo que ya tiene uno en esas fechas se queda como está y se
  dice; los de fuera (Julián, SQL antiguo) salen en la lista por tandas, solo para verlos.

PrestaShop mira los precios específicos por minutos: por eso un Special Price «desde ya» empieza en
el minuto en punto (con segundos tardaba hasta un minuto en verse).

Desde la 2.3.0 un Special Price puede llamarse **«Liquidación»** o **«Bajada de precio»** en toda la
tienda (etiqueta del listado, banda y etiqueta de la ficha, fila y vale de la cesta, Doofinder) y
llevar **cupo**: ver «Por cantidad: el cupo».

## La ficha, en los huecos del tema (3.0.0, 23/10/2026)

Hasta la 2.4 la ficha la pintaba `kpyproductbuy`, que preguntaba a este módulo por un hook suyo.
Julián no lo instala en producción (la ficha rediseñada vive en el tema `kpy_theme`), así que la
pinta este módulo en los hooks que el tema ya llama, dentro del formulario de compra
`#add-to-cart-or-refresh` (que PrestaShop repinta entero en cada refresco: formato, cantidad, cupón,
opción del regalo). Todo sale de una instancia por producto y petición (`KpcFicha::de()`); la regla
es pura (`KpcFicha::modelo`, `tests/ficha.php`) y el detalle está en
`docs/plan-desacoplar-kpycoupons.md` (apartado 3).

| Hook del tema | Qué pinta |
|---|---|
| `displayProductPriceBlock` (`type='weight'`, `hook_origin='product_sheet'`, solo `ProductController`; '' en el listado, la cesta y la ventana «Añadido», que llaman al mismo hook con otros `type`) | El bloque bajo el precio (`views/templates/hook/ficha.tpl`): el cupón (toda la caja es el botón: «Aplica tu cupón −10 %» / «Cupón −10 % aplicado · Quitar»), la 2ª unidad («Añade 1 ud. más: te ahorras…», pulsable mientras falte una), «Quedan N unidades en Liquidación», nuestro €/kg cuando el precio presentado no es el del core, la nota «El cupón −10 % es para: 3 kg, 12 kg», y con la tarjeta de regalo elegida la pastilla «Kilos gratis» (que `ficha.js` sube encima del precio), «Incluye 3 kg gratis (17,99 €), a 0 € en la cesta», «Promoción hasta…» y «quedan N regalos». Lleva el campo oculto `kpy_regalo` (0\|1) y los datos de las tarjetas de regalo (`data-kpc-datos`, `KpcFicha::tarjetas()`). |
| `displayProductAttributeFlags` (por `id_attribute`) | El chip de la tarjeta de cada formato (`KpcFlags::deFormato` o el nombre propio del Special Price), una franja por tarjeta: con chip nuestro, `ficha.css` esconde el tag «Precio especial» del tema en esa tarjeta. |
| `actionPresentProduct` (detrás de `kpyspecialprices`) | El **precio presentado** cuando no es el del core: la opción con regalo elegida o el cupo agotado para esta cesta (el precio habitual, sin su Special Price: `kpy_special_price = false`, y con ello la piel amarilla y el bloque «Precio habitual» de `kpyspecialprices` no salen) o el cupón puesto por el cliente (el precio con el cupón). Pisa con `offsetSet` `price`, `regular_price`, `regular_price_amount`, `has_discount`, `discount_type`, `discount_percentage`, `discount_percentage_absolute`, `discount_amount`, `discount_amount_to_display`, `discount_to_display` y `kpy_special_price`; **nunca `price_amount`** (el JSON-LD y `og:price` dicen el precio del feed: norma de Merchant) ni la fila cruda de `Product::getProductProperties` (su caché estática la comparte la cesta). |
| `actionProductFlagsModifier` | `KpcFlags::ajustar` y, en la ficha, fuera la «Special Price» de la foto cuando el precio presentado ya no lo es. |
| `actionFrontControllerSetVariables` (solo la ficha) | `$modules.kpycoupons` = `{id_product_attribute, estado, formatos[id_attribute] => {tipo, texto, fondo, color, quedan}}`: una oferta a Julián, ninguna plantilla nuestra lo lee. |
| `displayCartModalFooter` | La ventana «Añadido a tu carrito»: la promoción de la línea que acaba de entrar, como la cesta debajo del nombre (`KpcLineas::bloques`). |
| `actionFrontControllerSetMedia` (solo la ficha) | `views/css/ficha.css` (prioridad 800, como las hojas de Julián) y `views/js/ficha.js` (`defer`), con la fecha del fichero como versión. |

**Qué cambia el precio grande y qué no** (`KpcFicha::modelo`): la opción con regalo y el cupo
agotado siempre; el cupón **solo con la cookie puesta por el cliente** (clic en la caja, `?kpc=`, el
enlace del email, o el canal de la visita si no es un robot); nunca un cupón «para todos»
(`auto_web`: la caja dice «En la cesta lo pagas a…») ni para un robot (Googlebot, Storebot, AdsBot,
bingbot…, `KpcFicha::esRobot`), que tampoco reciben cookie. Así el robot de Merchant ve siempre el
precio del feed.

**`ficha.js`** (sin librerías): **monta la tarjeta «12 kg + 3 kg gratis» en la fila de formatos del
tema** (una copia de la tarjeta del formato disparador con las clases del tema, el mismo radio
`group[…]`, la banda «Kilos gratis», el precio de siempre sin su Special Price, el PVPR de los dos y el
€/kg con los kilos del regalo; la fila la pinta `product-variants.tpl` recorriendo los atributos y
no deja añadirla desde un hook), y pone el campo oculto `kpy_regalo` a 1 cuando el cliente la elige
(a 0 con cualquier otra), antes de que el tema sirva su formulario; tras cada refresco la vuelve a
montar y sube la pastilla «Kilos gratis» encima del precio. El clic en la caja del cupón llama a
`/module/kpycoupons/aplicar` y, si contesta `ok`, pide el refresco de PrestaShop con `eventType:
'updatedProductQuantity'` (el único con el que `core.js` manda la cantidad escrita y no la repone al
mínimo); la caja verde de la 2ª unidad sube la cantidad una unidad; al volver con «Atrás» se refresca
si el campo repuesto no cuadra con lo pintado; y durante una campaña copia los colores del bloque
(`--kpc-esp-bg/fg`) al contenedor del precio del tema. Sin JavaScript no hay tarjeta de regalo (como
con `kpyproductbuy`); el formato disparador enseña entonces el chip «+ 3 kg gratis».

**Lo que toca del tema, solo desde nuestra hoja y solo con nuestro bloque presente** (`ficha.css`):
`.attribute-flags .kpy-special-price-attribute-tag` y `.kpy-product-attribute-flag` se esconden en la
tarjeta que lleva chip nuestro; `.product__unit-price` (el €/kg del widget de `kpyproductblocks`, que
calcula con `Product::getPriceStatic` y no con el precio presentado) se esconde cuando el bloque
imprime el suyo; `.badge.discount` va en lila mientras el cupón está puesto; `.kpy-special-price-info`
(«Precio exclusivo online», que Rafa quitó el 07/10) se esconde cuando hay regalo o «Quedan N»; la piel
amarilla toma los colores de campaña. Lo que no se puede arreglar desde aquí está pedido a Julián
(apartado 9 del plan): el €/kg del widget leyendo `price_amount`, un hook por tarjeta, los textos.

## Cupón y 2ª unidad, en la ficha y en la cesta

- **Ficha**: este módulo (3.0.0, ver arriba). Pulsar el cupón llama a `/module/kpycoupons/aplicar`.
- **Cesta**: el descuento lo hace **PrestaShop** con una regla de carrito por cupón
  (`reduction_product = -2`), así sale bien en cesta, pago, pedido y factura.
  - **PrestaShop 9** conforma un grupo de restricciones con **una** de sus reglas (O). Por eso la
    regla va por **combinación exacta** (`combinations`) y el grupo «todas las reglas» (hasta la 1.3,
    EUKA10 descontaba el Boske de 15 kg por compartir «Saco de 15 Kg»).
  - **Productos sin combinaciones** (accesorios: 485 en pruebas) van en la cesta con combinación 0;
    si el cupón los lleva, el grupo tiene dos reglas que se cumplen las dos: «estos productos» y
    «estas combinaciones + la 0». Comprobado con Trixie −15 %: accesorio y arnés al céntimo; el
    excluido y un Boske, no.
  - Los vales los lista **PrestaShop** encima de «¿Tienes un cupón?» y se repintan en cada cambio;
    el módulo solo los **dibuja** (`views/js/cesta.js`, `views/css/cesta.css`) desde
    `displayShoppingCartAfterDetailedTotals`, con el empujón «Añade 1 ud. más de… y te ahorras…»
    como botón, y el ahorro del Special Price («Special Price en 1 producto −20,40 €»).
  - La 2ª unidad no lleva papelera. Quitar un cupón con la papelera lo olvida (no vuelve solo).
  - Un cupón que deja de descontar se quita solo. Solo un cupón de Kompy a la vez.
- **Cómo llega el cupón**:
  - ya puesto para todos (en la ficha de sus productos lo ve y lo lleva cualquiera, venga de donde
    venga; nunca sale en productos que no entran), o por canal: **Google** (`gclid`, `srsltid`, `utm_source=google`,
    `utm_medium=cpc`), **email** (`utm_medium=email`, `utm_source=newsletter`, Connectif,
    Klaviyo…) o **redes** (`fbclid`, `utm_source=instagram`, `utm_medium=social`…). El canal **se
    recuerda** (cookie `kpy_canal`) aunque aterrice en una categoría: el cupón entra en cuanto hay
    algo suyo en la cesta. Si el cliente lo quita, no vuelve mientras navega; una visita nueva desde
    ese canal lo vuelve a poner;
  - con el **enlace** `?kpc=CODIGO`;
  - **escrito en la cesta**: si es de Kompy pasa a ser el del cliente (hasta la 1.4 se quitaba al
    cambiar una cantidad).
- **Apagar** un cupón apaga su regla de carrito (hasta la 1.4 el código seguía valiendo).

## El reparto: calculado una vez y guardado

`KpcReparto` decide qué artículo le toca a cada promoción y lo guarda en `kpy_coupon_item`: al
guardar, apagar o borrar una promoción, al pulsar «Actualizar ahora», y cuando empieza o acaba una
promoción o un Special Price. Para eso último no hace falta cron: se apunta el próximo cambio
(`KPC_PROXIMO`, como mucho a un día vista) y la primera visita de después lo rehace **después de
contestar** (`fastcgi_finish_request`) y con cerrojo en la base de datos. La visita que lo dispara
tarda lo mismo que una normal (0,94 s frente a 1,02 s en pruebas, ruido).

Orden: primero los Special Price (sus precios deciden el resto), luego cupones y 2ª unidad. **Cada
promoción se calcula para su primer día**: una programada para el lunes no le quita nada hoy a la que
está en marcha. Red de seguridad opcional, un cron diario:
`1 0 * * * docker exec -w /var/www/html/kompy php85 php modules/kpycoupons/bin/sincronizar.php`.

## Las etiquetas de los listados

| Etiqueta | Cuándo | En Google Shopping |
|---|---|---|
| **Special Price** (crema, la de `kpyspecialprices`) | Special Price vivo que rebaja de verdad (desde el 5 %) | precio tachado + «Oferta» (`sale_price`) |
| **Código −10 %** (lila) | Hay cupón para ese producto | «Código de descuento» (promoción de Merchant) |
| **2ª ud. −30 %** (verde) | Hay promo de 2ª unidad | «Oferta especial» (`buy_m_get_n_percent_off`) |
| **+ 3 kg gratis** / **+ regalo** (violeta) | Hay regalo con la compra para ese formato | Promoción de Merchant, solo en los artículos sin Special Price |

Qué va con qué (Rafa, 30/09/2026):

- **Con Special Price no sale nada más que hable de precio**: ni «Oferta −X %» (salía sobre la foto y
  en las tarjetas de los formatos sin Special Price), ni «Bajada de precio», ni «Ahorro XL», ni las
  rebajas de PrestaShop. **Sí van con ella «Top ventas», «Nuevo»**, las de popularidad y «agotado».
- **Cada formato, su promoción**: si el saco de 12 kg tiene Special Price y el de 3 kg una 2ª unidad,
  en la ficha la foto dice la del formato elegido (y cada tarjeta de formato, la suya); en los
  listados y en el buscador, que enseñan el producto entero, salen las dos.
- **«Ahorro XL» solo en su ficha** (Rafa, 07/10/2026): habla del formato de más peso, y la tarjeta
  del listado enseña el precio de otro formato. En los listados, el buscador y las sugerencias de
  otra ficha no sale; en su ficha sí (sobre la foto del formato XL y en su tarjeta).
- Orden: Special Price, la promoción, las demás y «agotado» al final.

Lo hace `KpcFlags::ajustar` en `actionProductFlagsModifier` (detrás de `kpyproductflags` y
`kpyspecialprices`, delante de `kpypromolanding`), que además quita la «Special Price» si el precio no
está rebajado de verdad: del **precio habitual al de hoy**, con los precios de verdad y desde el
**5 %** (el mismo número que el feed).

**Se calculan en cada petición, de las tablas propias** (2.5.0; como pide Julián y como hace
`kpyspecialprices`): nada se escribe en las tablas de `kpyproductflags`. De `kpy_coupon` (las
promociones en vigor, en orden de mando), `kpy_coupon_item` (el reparto: qué formato lleva cada una),
`kpy_coupon_precio` (los Special Price propios, para llamarlos «Liquidación» u «Outlet») y
`kpy_coupon_regalo` (qué regala cada formato); y de `kpy_special_price` (la tabla de datos del
Special Price de Kompy, la de `kpyspecialprices`; **solo lectura**: `id_product`,
`id_product_attribute`, `id_shop`, `old_discount`, `date_from`, `expire`) sale si el precio está
rebajado de verdad. Una consulta por tabla y por página, en caché estática: un listado de 24
productos no hace 24 consultas. La caducidad la dan las fechas de la promoción (`enVigor` y
`vigentes` filtran con `NOW()`): la etiqueta se apaga a su hora, sin filas que caduquen.

- **Fuera de su ficha** (listados, buscador, sugerencias de otra ficha): la promoción **que manda** en
  el producto (`KpcFlags::elegir`): la primera en orden de mando de las de sus formatos, y un cupón o
  una 2ª unidad antes que un regalo.
- **En su ficha**: la del **formato elegido** (`id_product_attribute` del producto que se pinta) y,
  detrás, la de su regalo. De otro formato, nada: cada tarjeta lleva la suya.
- **El chip de cada tarjeta de formato** (`displayProductAttributeFlags`, que el tema llama en
  `product-variants.tpl`): `KpcFlags::deFormato` da «Cupón −10 %», «2ª ud. −30 %» o, si el formato
  solo tiene regalo, «+ 3 kg gratis» / «+ regalo» (14 caracteres como mucho, `CHIP_MAX`). Con solo
  Special Price, nada: el tema ya pinta «Precio especial» desde `$modules.kpyspecialprices`.
- Si todavía llega una fila vieja de la tabla de `kpyproductflags` con nuestro texto («Código −10 %»,
  «+ 3 kg gratis»), se tira (`KpcFlags::tipoPromo`), para que no salga dos veces.

**Contrato** (lo lee `kpypromolanding`, que va detrás en el hook y recolorea por campaña): las claves
`kpy_special_price` y `kpc_0`, `kpc_1`…; los `type` `kpc-cupon`, `kpc-segunda`, `kpc-regalo`,
`kpc-especial`, `kpc-liquidacion`, `kpc-outlet`, `kpc-bajada`; los colores de `KpcFlags::COLORES`; y
en cada etiqueta propia el campo **`kpc`** = `['tipo' => cupon|segunda_unidad|regalo|special_price,
'id_kpy_coupon' => int (0 en un Special Price cargado por SQL), 'pct' => float]`, para reconocerla
sin adivinar por el texto. El orden del hook `actionProductFlagsModifier` que hace falta: `kpygifts`,
`kpyproductflags`, `kpyspecialprices`, **`kpycoupons`**, `kpypromolanding` (Diseño → Posiciones).

Hasta la 2.4 las etiquetas eran filas en `kpy_flag`, `kpy_flag_lang` y `kpy_product_flag`
(`KpcFlags::escribir`, en cada reparto). El `upgrade` 2.5.0 y `uninstall()` las borran
(`KpcFlags::limpiarTablasAjenas`: los tipos «Oferta −%», «Código −%», «2ª ud. −%» y «+ %», con el
menos tipográfico y con el guion normal, en el orden de la clave foránea; las manuales de Julián no se
tocan; se puede repetir). La regla (qué sale, en qué orden, qué chip) es pura y se prueba sin tienda
(`tests/flags.php`). Detalle en `docs/etiquetas-y-google.md`.

## Google Merchant y Doofinder

| Fichero | Qué lleva | Dónde va en Merchant |
|---|---|---|
| `promociones.txt` | Cupones y 2ª unidad en marcha **y programadas** (Google las revisa antes), si están marcadas para anunciar | Marketing → Promociones |
| `precio-oferta.txt` | Special Price: `price` (habitual) y `sale_price` con fechas | Fuente complementaria |
| `productos-promo.txt` | La promoción de carrito de cada artículo (`promotion_id`). Sin `custom_label_4`: en el feed de Julián las cinco etiquetas ya están usadas (la 4 es el margen) | Fuente complementaria |

Un Special Price **nunca** va como promoción (Google lo rechazaría). Cada fichero tiene su dirección
con clave (`KPC_CLAVE_MERCHANT`) para que Merchant lo recoja solo. En el feed, un producto sin
combinaciones va con su id solo (a confirmar con Julián que así lo manda el feed principal).

Según la especificación de Google (comprobada el 30/09/2026):

- la 2ª unidad es `buy_m_get_n_percent_off` con `buy_this_quantity` 1 y
  **`get_this_quantity_discounted`** 1 (hasta la 1.5 salía como `get_this_quantity`, y Google la
  habría rechazado por faltarle ese atributo);
- `promotion_destination` = `Shopping_ads`. `Free_listings` solo vale en Estados Unidos, y varios
  destinos irían en columnas separadas (hasta la 1.5 iban juntos, separados por comas);
- el código de un cupón, 20 caracteres como mucho (`generic_redemption_code`).

**Doofinder**: cuatro columnas más al final de su feed de siempre, por `actionDoofinderExtendFeed`, el
enganche oficial de su módulo para añadir columnas desde otro (no se toca Doofinder, la dirección del
feed es la misma y actualizar Doofinder no lo borra):

| Columna | Qué lleva | En la plantilla del buscador |
|---|---|---|
| `kpy_especial` | «1» si algún formato tiene un Special Price que rebaja de verdad | la etiqueta «Precio especial» |
| `kpy_precio_habitual` | «60,19 €», solo si el formato por defecto (el precio que enseña la tarjeta) es el del Special Price | tachado, con el precio en amarillo |
| `kpy_promo` | «2ª ud. −30 %» o «Código −10 %» | su etiqueta |
| `kpy_promo_tipo` | «segunda» o «cupon» | el color (verde o lila) |

La plantilla y el CSS del buscador para pegar en Doofinder están en
`C:\kompy-web\docs\doofinder\`. Hasta la 1.6 salían `kpy_flag`, `kpy_flag_color`, `kpy_flag_texto` y
`kpy_flag_tipo`, que no llegaron a usarse.

Doofinder junta estas filas con las suyas por `id_product` (`arrayMergeByIdProduct`, en
`feeds/product.php`) y solo usa la primera de cada producto, así que si otro módulo ya trae fila
para uno, las columnas se añaden a esa. Si el feed se pide por páginas (`limit`/`offset`), van solo
las filas de los productos de esa página (`DfTools::getAvailableProductsIds`, la misma consulta que
usa el feed): con una página vacía, Doofinder daría las nuestras por productos. Probado en pruebas
el 30/09 llamando al enganche como lo llama su feed: 17 productos con columnas, las mismas 17 por
páginas de 500, 0 en una página vacía.

## Las reglas de carrito de cada cesta

La 2ª unidad no puede ser una regla para todos (su descuento depende de lo que lleve cada cesta), así
que cada cesta que la activa tiene la suya (`KPY2U-{promoción}-{cesta}-{firma}`, en Catálogo →
Descuentos). Desde la 1.7:

- **duran lo que dura la promoción** (hasta la 1.6, un mes desde que se creaban, aunque la promoción
  acabara antes); una promoción sin fin, una semana que se alarga sola al tocar la cesta;
- **se barren solas** cada 6 horas, en la primera visita y después de contestar (`KpcLimpieza`, sin
  cron): las de cestas abandonadas (más de 2 días sin tocarse), caducadas, de promociones terminadas
  o de cestas que ya son un pedido que no las usó. Las que están en un pedido no se tocan nunca;
- si el cliente vuelve a una cesta vieja, la regla se crea otra vez **al ver la cesta** o el pago, o
  al cambiarla, con el importe de ese momento;
- no se reescriben si no cambia nada (antes, en cada cambio de la cesta).

El cupón con código no crea nada por cesta: es una sola regla para todos. El regalo con la compra
sí (`KPYRG-{cesta}-{producto regalado}-{firma}`, una por producto regalado, de importe lo que valen
las unidades regaladas): se barre igual, y se borra sola en cuanto el regalo sale de la cesta. Lo
mismo el Special Price de las unidades de la tarjeta de siempre en una línea con regalo
(`KPYSP-{cesta}-{producto}-{firma}`, desde la 2.2.0). Con el cupo (2.3.0) hay dos usos más: el
mismo `KPYSP-…` para las unidades de un Special Price que caben cuando la línea va al precio
habitual (el vale se llama como la promoción: «Liquidación: 2 × …»), y
`KPYCP-{promoción}-{cesta}-{producto}-{firma}` para un cupón con cupo (un vale por producto con lo
que cabe; la regla global del cupón no entra en esa cesta). Los prefijos, en `KpcVale::PREFIJOS`;
se barren todos igual.

## Cargar promociones de golpe (catalog-ops)

`catalog-ops/merchant_promos/registro.csv` + `articulos/<promotion_id>.csv` → `bin/promos-tienda.php
--sql` → el .sql para Julián. Desde la 1.5.0 **todas** las promociones, también el Special Price, se
apuntan en `kpy_coupon`: el SQL no escribe precios; los pone el módulo con el mismo cálculo que la
pantalla (en la primera visita después, o al momento con `bin/sincronizar.php`). Así salen en la
pantalla y se pueden cambiar ahí. La lista de artículos de un Special Price puede traer las columnas
`descuento` (su %) o `precio` (su precio final con IVA). **Requiere kpycoupons 1.5.0 o posterior.**

```
php modules/kpycoupons/bin/sincronizar.php         # reparto, reglas, Special Price y etiquetas
php modules/kpycoupons/bin/sincronizar.php --ver   # solo enseña el estado de cada promoción
```

## Pantalla y código

`AdminKpyPromociones` (controlador propio: la página «Configurar» de PrestaShop 9 va dentro de
Symfony y no deja subir ficheros con normalidad) + `KpcPantalla` + `views/templates/admin/pantalla.tpl`
(con `lista.tpl`, la lista de arriba, y `productos.tpl`, los productos de una promoción),
`views/js/admin.js`, `views/css/admin.css`. «Configurar» en la lista de módulos lleva ahí.

Hasta la 1.4 la pantalla fallaba al abrirla en PrestaShop 9: *Call to protected method
ModuleCore::trans() from scope KpcPantalla*. Ahora traduce por el módulo (`Kpycoupons::tAdmin`). El
mismo fallo estaba en `kpycrosssell` (`KcsPantalla`), arreglado igual.

En la 1.5 el JS de la pantalla no hacía nada en el back office de verdad: PrestaShop lo carga en la
cabecera, antes que el formulario, y el JS buscaba el formulario al cargarse. Desde la 1.6 arranca
cuando la página está lista (y si algo fallara, lo dice en la pantalla en vez de callarse). Se
prueba igual que lo sirve PrestaShop: el JS en la cabecera.

Las clases se cargan **cuando hacen falta** (`spl_autoload_register` en `kpycoupons.php`), no todas en
cada visita: la ficha y los listados solo usan las que leen lo guardado; el cálculo y la pantalla
son del back office. Medido en pruebas el 30/09 (A/B con la 1.5): la ficha tarda lo mismo y su HTML
es idéntico.

| Clase | Qué hace |
|---|---|
| `KpcElegibles` | La regla pura: a qué combinaciones llega una promoción (filtros, gamas, tamaños, lista, fuera, niveles de pack, OUTLET) |
| `KpcFormatos` | El tamaño («saco 12 kg») y el nivel de pack de cada formato, con la tabla de packs o por el nombre |
| `KpcAlcance` | Pregunta a la tienda qué productos pueden entrar y sus datos (solo lo que se vende); separa gamas de categorías generales |
| `KpcLista` | Lee listas y Excel: EAN, referencias, ids, enlaces, con su % o su precio |
| `KpcReparto` | El reparto guardado, cuándo rehacerlo y los lectores de la ficha y la cesta |
| `KpcEspecial` | Los Special Price: poner, quitar, calcular (con PriceShape) |
| `KpcCupon` | La promoción y la regla de carrito del cupón |
| `KpcCarrito`, `KpcSegunda`, `KpcResumen` | La cesta: cupón, 2ª unidad y lo que se le cuenta al cliente |
| `KpcFlags`, `KpcMerchant` | Etiquetas y ficheros de Google |
| `KpcRegalo`, `KpcLineas` | El regalo con la compra (motor, elecciones, lo que queda en el pedido) y la cesta partida en filas |
| `KpcCupo`, `KpcCupoCesta` | El cupo: cuántas quedan (vendidas, reservas), el reparto por orden, lo que cabe en cada cesta (Special Price, cupón, 2ª unidad, regalo), agotar y reabrir |
| `KpcVale`, `KpcPrecioCesta` | Los vales de una cesta (`KPY2U`, `KPYRG`, `KPYSP`, `KPYCP`) y los precios de una sola cesta (`specific_price.id_cart`) |
| `KpcLimpieza`, `KpcPantalla` | El barrido de vales, reservas y precios de cestas muertas; la pantalla |
| `KpcFicha`, `KpcEnvase` | La ficha en los huecos del tema (3.0.0): qué bloque se pinta y qué precio se presenta (regla pura), y el lector de nombres de formato («Saco de 12 Kg» → 12 kg, €/kg, €/lata) |
| `KpcContrato` | Lo que se cuenta a los demás módulos por los hooks: la fila pública de una promoción (claves fijas, versionadas), el alcance y el estado |

## Contratos públicos (3.0.0)

Regla de Julián (23/10/2026): ningún módulo llama a funciones de otro. Lo que otros módulos pueden
usar de este es **solo** lo de aquí; lo demás es interno y puede cambiar sin aviso. Quien dispara un
hook lo nombra, lo crea (fila en `hook` con descripción, `Kpycoupons::HOOKS_PROPIOS`) y documenta sus
parámetros; los parámetros son datos curados (`KpcContrato`, `version` 1), no filas crudas ni objetos.

**Hooks que dispara este módulo** (todos con `try/catch`: un fallo del oyente no para nada):

- `displayKpyCouponsFormulario` — al pintar el formulario de una promoción, módulo a módulo en el orden
  de Diseño → Posiciones. Params: `version`, `id` (0 nueva), `valores` (los campos del formulario),
  `duplicar`, `paso` (6, 7…: el número solo se gasta si el módulo devuelve algo), `url_editar`,
  `url_ver`. Devuelve HTML; maquetación con las clases `kpc-paso`, `kpc-paso__titulo`, `kpc-num`,
  `kpc-opcional`, `kpc-ayuda`, `kpc-fila`, `kpc-campo(--ancho|--pct)`, `kpc-radios`, `kpc-check(s)`,
  `kpc-nota`, `kpc-pregunta`, `kpc-pastilla` bajo `.kpc-admin`.
- `actionKpyCouponsGuardar` — tras guardar la fila y sus ámbitos, antes del reparto. Params: `version`,
  `id`, `valores`, `promocion` (la fila pública, abajo).
- `actionKpyCouponsReparto` — al final de cada reparto (guardar, cambio de fecha tras una visita,
  consola, `upgrade`). Params: `version`, `id_shop`, `items` (id ⇒ [clave ⇒ id_product]; la clave es
  `id_product_attribute`, o −id_product sin combinaciones), `avisos` (id ⇒ string[]), `alcance` (id ⇒
  {entran, especial}, misma clave, antes de choques y agotados), `promociones` (id ⇒ fila pública),
  `guardada` (id recién guardado, 0 si no). Puede correr tras contestar una visita: sin salida.
- `actionKpyCouponsResumen` — al pintar los totales de la cesta (dos veces por página). Params: `cart`,
  `resumen` por referencia {vales[id_cart_rule]{tipo, texto, codigo, id_kpy_coupon, pct}, lineas[]{clase,
  tipo, n, texto, importe}, animos[]{texto, sube}}. El oyente puede añadir a un vale `fondo` y `color`
  (`#rrggbb`) y a una línea `estilo` y `estilo_icono` (solo `background`/`color`: se filtran).
- `actionKpyCouponsCampana` (consulta, `array_return`) — al pintar la ficha. Params: `id_product`,
  `id_product_attribute`, `tipos` (string[]). Respuesta: null o {nombre, clave, piel negra|clara,
  tipos ⇒ {label, background, color}}; se usa la primera con `tipos`. La contesta `kpypromolanding`.

**Hooks que contesta este módulo** (los dispara quien pregunta, con `array_return`; sin este módulo no
pasa nada): `actionKpyCouponsProductosConPromo` (`cart`, `id_shop`, `productos` int[] por referencia:
se añaden los id_product de la cesta con cupón puesto, 2ª unidad, línea con regalo o regalado; lo usa
`kpycrosssell`), `actionKpyCouponsAlcance` (`id`, `id_shop` → {version, entran, especial, promocion} o
null; lo usa `kpysupply`) y `actionKpyCouponsPromociones` (`id_shop` → por id la fila pública más
`items` y `parejas` [{clave, id_product, r_id_product, r_ipa, n}]; lo usa `kpypromolanding`).

**La fila pública de una promoción** (`KpcContrato::promocion`): `id_kpy_coupon`, `codigo`, `nombre`,
`tipo` (cupon|segunda_unidad|special_price|regalo), `porcentaje`, `activo` (bool), `fecha_desde`,
`fecha_hasta` (string o null), `etiqueta` (''|liquidacion|outlet|bajada), `estado`
(viva|programada|terminada|agotada|apagada), `cajon` (''|liquidacion|outlet), `regalo_clase`
(kilos|regalo), `auto_web`, `en_ficha`, `cupo`, `cupo_lista`, `cupo_agotado`, `id_manufacturer` (el
del ámbito de marca, 0 si no), `tamanos` y `packs` (tal como se escribieron), `ambitos` [{tipo, id, valor,
unidades, agotado}]. Una clave nueva se
añade sin quitar las que hay; quitar una sube `version`.

**Hooks del core que contesta y lo que deja**: `actionProductFlagsModifier` (detrás de
`kpyproductflags` y `kpyspecialprices`: claves `kpy_special_price`, `kpc_0`, `kpc_1`…; `type`
`kpc-cupon|kpc-segunda|kpc-regalo|kpc-liquidacion|kpc-outlet|kpc-bajada`; `label`, `background`,
`color`; campo `kpc` = {tipo, id_kpy_coupon, pct}; colores `KpcFlags::COLORES`);
`displayProductAttributeFlags` (`<div class="kpc-attr kpc-attr--{type}">`); `displayProductPriceBlock`
(solo `weight` + `product_sheet`); `actionPresentProduct` (las claves de la tabla de «La ficha»);
`actionPresentCartProduct`/`actionPresentCart` (en la línea presentada `kpy_is_gift`, `kpy_regalo`,
`kpy_parte`, `kpy_disparador`; URLs con `&kpy_regalo=0|1`); `displayCartExtraProductActions` (el
primero); `actionFrontControllerSetVariables` (`$modules.kpycoupons`); `actionDoofinderExtendFeed`
(columnas `kpy_especial`, `kpy_precio_habitual`, `kpy_promo`, `kpy_promo_tipo`; se apaga con
`KPC_DOOFINDER = 0`).

**Petición, cookie, URL, HTTP, pestaña**: el campo `kpy_regalo` (0|1) del formulario de la ficha
(viaja en el refresco y al añadir); la cookie de PrestaShop `kpy_cupon` (`CODIGO` puesto, `!CODIGO`
quitado a mano) y `kpy_canal` (shopping|email|social); el parámetro `?kpc=CODIGO`
(`^[A-Za-z0-9_-]{2,32}$`) en cualquier URL; `POST /module/kpycoupons/aplicar` (`codigo`,
`accion=poner|quitar` → `{ok, aplicado, codigo, pct, descuento}` o `{ok: false, motivo}`); la pestaña
`AdminKpyPromociones` (`&editar=ID`, `&ver=ID#kpc-promo-ID`).

**Vales y tablas**: las reglas de carrito `KPY2U-`, `KPYRG-`, `KPYSP-`, `KPYCP-` las crea y borra este
módulo (no borrar a mano). Tablas propias que otros pueden **leer** (columnas estables; cambiarlas =
versión mayor): `kpy_coupon`, `kpy_coupon_item`, `kpy_coupon_scope`, `kpy_coupon_regalo`,
`kpy_coupon_regalo_pedido`, `kpy_coupon_cupo_pedido` (ver «Tablas»). Nadie escribe en ellas.

**Escrituras fuera de lo propio**, dichas enteras: por la API del core, `specific_price`
(`SpecificPrice`), `cart_rule` (`CartRule`), `cart` (`Cart`), `tab` (`Tab`); en crudo por falta de
API, `cart_rule_product_rule_group/_rule/_value` (el mismo SQL que `AdminCartRulesController`); y las
que piden el acuerdo de Julián, `kpy_special_price` (los Special Price de este módulo, apuntados en
`kpy_coupon_precio`) y `order_detail.product_name` («(regalo)»). Las etiquetas viejas en `kpy_flag*`
las borra el SQL del paquete, no el módulo.

## Tablas

Crea las suyas; fuera de ellas solo escribe los Special Price (`specific_price` y
`kpy_special_price`, solo los suyos). En las tablas de `kpyproductflags` no escribe nada desde la
3.0.0 (las etiquetas se calculan por hook; lo que había hasta la 2.4 lo borra el SQL del paquete o
`bin/limpiar-flags.php --borrar`, no el módulo: son tablas ajenas), y de otros módulos
solo **lee** `kpy_special_price` (ver «Las etiquetas de los listados»).

- `kpy_coupon`: la promoción: tipo, modo del Special Price, %, fechas, casillas, **tamaños**
  (`tamanos`, uno por línea) y **niveles de pack** (`packs`: «1» sueltos, «2» packs de 2, «2+»
  cualquier pack; vacío = todos), la lista tal como se pegó, regla de carrito y el resumen del
  reparto (`formatos`, `aviso`).
- `kpy_coupon_scope`: filtros (`marca`, `categoria` —gama si cuelga de una marca elegida—,
  `caracteristica`), lista (`producto`,
  `combinacion`, con su `valor` si lo lleva) y fuera (`excluir_producto`, `excluir_combinacion`).
- `kpy_coupon_item`: el reparto: qué artículo le toca a cada promoción (`clave`: la combinación, o
  el producto en negativo si no tiene).
- `kpy_coupon_precio`: los Special Price que ha puesto cada promoción.
- `kpy_coupon_regalo`: en un regalo, qué regala cada disparador (`clave` → `r_id_product`/`r_ipa`,
  la reserva `s_*`, `n` regalos por unidad, `origen`: pequeno, mismo, mano o reserva).
- `kpy_coupon_regalo_cesta`: qué parte de cada línea de una cesta es regalo (por promoción y
  disparador, y cuántas unidades del disparador lo llevan, `unidades`); se rehace en cada guardado
  de la cesta y se borra al pedir.
- `kpy_coupon_regalo_eleccion`: cuántas unidades de cada línea ha pedido el cliente con regalo
  (`unidades`). Se borra al pedir y al barrer cestas muertas. (Hasta la 2.2 llevaba también el
  precio de cesta, `id_specific_price`; desde la 2.3.0 está en `kpy_coupon_precio_cesta`.)
- `kpy_coupon_regalo_pedido`: lo regalado en cada pedido, con precio y coste de la unidad: el
  sell-out para la marca y el cupo de la campaña. No se borra nunca.
- `kpy_coupon_cupo_cesta` (2.3.0): las reservas del cupo: por cesta, promoción y `clave`, cuántas
  unidades lleva (`unidades`, `date_upd`). Se rehace en cada guardado de la cesta, se borra al pedir
  y se barre con las cestas muertas.
- `kpy_coupon_cupo_pedido` (2.3.0): lo vendido con cupo en cada pedido (promoción, `clave`,
  `unidades`); los regalos no van aquí (los cuenta `kpy_coupon_regalo_pedido`). No se borra nunca.
- `kpy_coupon_precio_cesta` (2.3.0): el precio de una sola cesta que el módulo ha puesto a una línea
  (`id_specific_price`), por cesta y `clave`: para la tarjeta de siempre en una línea con regalo y
  para las unidades que no caben en un Special Price con cupo. Se borra con el precio.

Las columnas del regalo en `kpy_coupon`: `regalo_modo`, `regalo_por`, `regalo_tope` (desde la 2.3.0
sigue al `cupo`), `regalo_packs`, `regalo_mismo`, `regalo_reserva`, `regalo_parejas`. Las del cupo
(2.3.0): `cupo` (0 = sin límite), `cupo_agotado` (fecha, o NULL), `cupo_ficha` (enseñar cuántas
quedan) y `etiqueta` ('' = «Special Price», `liquidacion`, `outlet`, `bajada`); y (2.4.0)
`cupo_lista` (las unidades van en la lista). En `kpy_coupon_scope` (2.4.0): `unidades` (las de ese
artículo de la lista, NULL si no trae) y `agotado` (cuándo vendió las suyas). Y fuera de sus tablas, el «(regalo)» en
`order_detail.product_name` de la línea regalada.

Al desinstalar las tablas se quedan (son datos de Kompy).

## Probar

```
php tests/elegibles.php    # a qué formatos llega (filtros, lista, fuera, packs, OUTLET)
php tests/formatos.php     # tamaños y niveles de pack (nombres de verdad), gamas, los ejemplos de Rafa
php tests/lista.php        # listas y Excel de proveedor, con su % o su precio (y el EAN compartido)
php tests/especial.php     # las cuentas del Special Price (encima de PriceShape)
php tests/flags.php        # las etiquetas: el %, qué sale en su ficha y en los listados, el chip de cada formato
php tests/regalo.php       # el regalo: qué pequeño toca, las parejas y la cesta en todas las direcciones
php tests/cupo.php         # el cupo: cuántas quedan, el reparto de lo que cabe, la 2ª unidad con tope, los nombres
```

(`regalo.php` y `cupo.php` usan `mb_*`: con un PHP sin php.ini, `php -n -d extension_dir=… -d
extension=mbstring tests/regalo.php`.)

Los guiones **de servidor** van en `tests/servidor/` y se ejecutan en la tienda de pruebas, dentro
del contenedor (`cat tests/servidor/cupo-pruebas.php | docker exec -i -u www-data -w
/var/www/html/kompy php85 php -- [--pedido]`): crean promociones y cestas del cliente de desarrollo,
apagan las demás promociones mientras duran (y las vuelven a encender, pase lo que pase), y con
`--pedido` hacen un pedido de verdad por transferencia, sin email. Nunca en producción.
`cupo-pruebas.php` (el cupo, 96 comprobaciones), `regalo-pruebas.php` (el regalo, 113) y
`mezcla-pruebas.php` (tres promociones en el mismo producto, 31).

2.4.0, probado el 19/10/2026 por la tarde en pruebas (`tests/servidor/cupo-pruebas.php`, sección J;
126 comprobaciones en total, y otra vez con `--pedido`): un «Outlet» −30 % con las unidades en la
lista (2 del 12 kg, 3 del 3 kg, sin tope total): llega a 2 artículos, la landing lo manda al cajón
`outlet` (categoría «Outlet y liquidación» con el producto, sin categoría de marca); una cesta con
3 × 12 kg y 2 × 3 kg reserva 2 y 2, el 12 kg parte (línea al habitual, vale «Outlet: 2 × …», aviso
solo en esa línea) y el 3 kg va entero al precio outlet sin partir; desde otra cesta la ficha dice
«Outlet» y lo que queda de cada formato (0 y 1); la etiqueta del listado, «Outlet» en gris oscuro;
el pedido agota el 12 kg (no el 3 kg) y la promoción sigue: tras el reparto el 12 kg vuelve al
precio de siempre sin etiqueta y la ficha solo nombra el 3 kg; guardar la misma lista no reabre el
12 kg, darle 5 unidades sí (quedan 3, recupera el precio); otra cesta con 2 × 3 kg solo lleva 1
(vale de 1); «Reabrir» limpia los artículos agotados. La regresión (regalo 113, mezcla 31, las puras
de los tres módulos) en verde.

2.3.0, probado el 19/10/2026 en pruebas **con cestas y un pedido de verdad**
(`tests/servidor/cupo-pruebas.php`, 96 comprobaciones; pedidos #866299 y #866300): Liquidación
−20 % con cupo 5 en dos cestas (3 + 3: la segunda paga 2 en liquidación y 1 al habitual, 166,37 €,
con el vale «Liquidación: 2 × …» y el aviso en su fila); una cesta suelta y la otra se las queda al
repasarse; una reserva de hace dos horas ya no cuenta; la ficha, la etiqueta del listado y Doofinder
dicen «Liquidación» y cuántas quedan; el pedido se lleva las 5 (5 líneas a 51,19 €, apuntadas en
`kpy_coupon_cupo_pedido`), la promoción queda agotada con fecha, ya no está en vigor, toca rehacer,
el precio del catálogo vuelve a 63,99 €, la cesta que la tenía vuelve al habitual sin precio de
cesta, vales ni avisos, la ficha y la etiqueta la olvidan y la landing la da por terminada; reabrir
sin unidades (una cesta nueva paga el habitual) y subir el cupo a 7 (quedan 2 y la cesta recupera el
precio); 2ª unidad con cupo 1 (4 uds.: una rebajada, aviso, la siguiente cesta sin vale ni
empujón); cupón con cupo 2 (vale `KPYCP` por producto, la regla global fuera, dibujado como cupón,
papelera que olvida, vuelve al elegirlo, la siguiente cesta sin vale); regalo con cupo 1 en dos
cestas (la segunda sin regalo y sin pagar de más; al liberarse la reserva se lo lleva y la primera
lo pierde al volver); limpieza sin reservas, precios ni vales huérfanos. Lo que salió: el #866299
salió con 3 unidades teniendo 5 (la caché estática de paquetes de PrestaShop, ver «Por cantidad»);
arreglado, el #866300 salió con las 5.

2.0.0, probado el 16/10/2026 en pruebas **con cestas y un pedido de verdad** (cliente de desarrollo,
transferencia, sin email; guion `regalo-pruebas.php` con 90 comprobaciones), promoción «Boske, el
pequeño de la misma ficha»: llega a 7 sacos (13 sueltos sin pequeño, dichos); añadir el 12 kg
(57,59 €) mete el 3 kg (17,99 €, ya con su Special Price) y la regla lo descuenta exacto (productos
75,58 − descuentos 17,99 = 57,59); dos sacos, dos regalos; bajar, uno; borrar la línea del regalo,
vuelve; comprar un 3 kg suelto, se paga uno (75,58); quitar el saco, se va el regalo y la regla se
borra; perro + gato, dos regalos y dos reglas; con un cupón del 10 % encima, el regalo sigue a 0 €
(cupón 7,56, regla 16,19, total 51,83); 2ª unidad en el 3 kg sin contar el regalado (con 3 uds, un par:
5,40); «los packs también regalan» (2 × 12 regala dos); uno por pedido; agotado (aviso en la cesta) y
reserva (entra la caja de latas; vuelve el stock, vuelve el 3 kg); fin de la promoción (se va y se
borra la regla; vuelve al reabrirla); línea «Gift» en la cesta, vale «Regalo: 1 × …», «(regalo)» en
el email, la ficha con «+ 3 kg gratis», la etiqueta del listado, Google `free_gift` y Doofinder;
**pedido #866295**: dos líneas (la del 3 kg «(regalo)», EAN 8437019628062, 17,99 €), un vale de
17,99, total pagado 57,59 (envío gratis), sell-out apuntado, la cesta pedida sigue sabiendo qué era
regalo; tope 1 con uno dado: el siguiente sin regalo y la cesta lo dice. Los pedidos #866293 a
#866295 son de estas pruebas (cliente de desarrollo, «pendiente de transferencia»: ese estado no
entra en la cola del despachador, no se mandan a ningún almacén).

1.6.0, probado el 30/09/2026 en pruebas con los ejemplos de Rafa (sin guardar, con «Comprobar»):

| Promoción | Llega a |
|---|---|
| Boske, Special Price −10 %, toda la marca | 39 artículos de 13 productos (sacos, sus packs, latas en packs de 6, 12 y 24) |
| Royal Canin Razas gato, sacos de 10 kg, −15 % | 16: los 8 sacos de 10 kg y sus 8 packs de 2 |
| Farmina, sacos de 12 kg, 2ª unidad al 20 % | 35 sacos, sin packs |
| Farmina, sacos de menos de 3 kg, 2ª unidad al 40 % | 126 sacos (43 de 1,5 kg, 40 de 2 kg, 43 de 2,5 kg) |
| Eukanuba, lista de EAN con su PVPR | el saco a su precio y su pack con el mismo %; los que no bajan y el EAN que no existe, dichos |
| Schesir, packs de 2, −10 % | 164: el pack doble de cada producto, ni sueltos ni de 4 |
| Advance, 2 referencias, 2ª unidad al 30 % | los 2 sacos, sin packs |

Y guardando: Royal Canin (16 Special Price al 15 %, la ficha a 112,43 €, reabierta con gama, tamaño
y packs marcados, borrada con los precios de vuelta) y un cupón Farmina solo 12 kg (regla de carrito
con las 70 combinaciones justas; en la cesta, −6,49 € al saco de 12 kg y nada al de 2 kg). La
regresión de la cesta, igual que el 29/09.

1.5.0, probado el 30/09/2026 en pruebas, de punta a punta, con promociones de prueba ya borradas (y
los precios vueltos a los de siempre):

- **Special Price**: Boske −20 % a toda la marca (ficha, listados, cesta, Google); quitar un
  producto a mano y volver a meterlo; apagar y encender; precio final por artículo desde una lista
  pegada (precio que no baja, pack que hereda, uno con Special Price de fuera, EAN que no existe);
  % por artículo con un accesorio sin formatos;
- **cupones**: Trixie −15 % (1.310 artículos, 80 sin combinaciones, uno fuera por EAN), programada
  para mañana, enlace `?kpc=`, cesta mezclada al céntimo, papelera, código escrito a mano, canal
  email (entra solo; desde Google no; visita normal no), Excel de proveedor;
- **regresión** de la cesta (2ª unidad de 1 a 4 uds., EUKA10 que no entra en Boske): igual que el 29/09;
- **pantalla** en ordenador y móvil (sin desbordes), sin errores de JS.

1.6.1, probado el 30/09/2026 en pruebas con una promoción de verdad que terminaba a los 8 minutos
(Special Price −15 % en el Royal Canin Mini Adult 8+, 8 kg y pack 2 × 8 kg):

- **mientras dura**: en la foto y en el listado solo «Special Price»; franjas «Special Price» solo en
  8 kg y 2 × 8 kg (2 kg y 4 kg, sin nada); 44,51 € con «Precio habitual 52,37 €» y «Termina hoy»;
  Google con `sale_price` y sus fechas; Doofinder «Special Price»;
- **al terminar** (13:01:59): en el minuto siguiente, 52,37 €, sin etiqueta y sin «Special Price» en
  la ficha nueva; tras la primera visita, sus precios y filas quitados, Google y Doofinder sin él.
  La ficha de siempre, en esa primera visita, aún enseñó el bloque de `kpyspecialprices` (sin su
  parche en pruebas): con el parche no sale nunca;
- **cupón** que terminaba a los 4 minutos: «Código −10 %» mientras dura; fuera a su hora.

## Instalar o actualizar

1. Copiar la carpeta a `modules/kpycoupons`.
2. `php bin/console prestashop:module install kpycoupons` o, si ya está,
   `php bin/console prestashop:module upgrade kpycoupons`:
   - `upgrade-1.4.0.php`: el hook de la cesta;
   - `upgrade-1.5.0.php`: columnas y tablas nuevas, el hook `actionFrontControllerInitAfter`, la
     pestaña, y **lo rehace todo** (reparto, reglas por combinación exacta, etiquetas);
   - `upgrade-1.6.0.php`: las columnas `tamanos` y `packs`, y lo rehace todo con los niveles de pack
     nuevos;
   - `upgrade-1.6.1.php`: con Special Price solo «Special Price»; borra las «Oferta −X %» y lo
     rehace todo;
   - `upgrade-1.7.0.php`: etiquetas por formato, «Top ventas» y «Nuevo» junto a «Special Price»,
     columnas nuevas de Doofinder, reglas de cesta hasta el fin de su promoción y el primer barrido
     de las que sobran;
   - `upgrade-1.8.0.php`: nada que hacer. La 1.8.0 solo añade un aviso al final de
     `KpcReparto::rehacer()`: el hook `actionKpyCouponsReparto` (`id_shop`, `items`, `avisos`), que
     escucha la landing de promociones (`kpypromolanding`) para colocar las categorías y los
     banners. Un fallo de quien escuche no para el reparto: se apunta en el registro;
   - `upgrade-1.9.0.php`: nada que hacer. La 1.9.0 añade tres huecos más para la landing de
     promociones, todos con `try/catch` (un fallo de quien escuche no para nada):
     `displayKpyCouponsFormulario` (`id`, `valores`, `duplicar`), lo que otro módulo pinta al final
     del formulario, entre el paso 5 y la barra de guardar (la landing pone ahí su paso 6, «En la
     landing»); `actionKpyCouponsGuardar` (`id`, `valores`, `cupon`), al guardar una promoción,
     después de sus ámbitos y **antes** del reparto; y `actionKpyCouponsResumen` (`cart`, `resumen`
     por referencia), al final de `KpcResumen::deLaCesta()`, para repasar los textos de la cesta
     (durante una campaña: «Cupón Black −10 % · EUKA10»). Los vales llevan ahora `id_kpy_coupon` y
     `pct`, y la línea del Special Price `tipo` y `n`. Quien escuche puede añadir a un vale `fondo`
     y `color` (`cesta.js` pinta la píldora con ellos) y a una línea `estilo` y `estilo_icono`
     (`carrito.tpl` los pone tal cual): así la cesta lleva los colores de la campaña. Las hojas y
     los scripts se registran con la fecha del fichero como versión (`cesta.css?202609292157`), para
     que el navegador no se quede con uno viejo al desplegar;
   - `upgrade-2.0.0.php`: el **regalo con la compra**: el tipo `regalo` en `kpy_coupon` y sus siete
     columnas, las tres tablas nuevas (`kpy_coupon_regalo`, `_cesta`, `_pedido`) y cuatro hooks:
     `actionValidateOrder` (apuntar lo regalado y «(regalo)» en la línea del pedido),
     `actionPresentCartProduct` y `actionPresentProductListing` (la línea de regalo en la cesta, la
     confirmación y el historial: `kpy_is_gift`, el mismo que ya pinta el tema por `kpygifts`) y
     `actionPaymentModuleProductVarTplAfter` («(regalo)» en el email). Se puede ejecutar más de una
     vez. Iba con `kpyproductbuy` ≥ 1.2.0 (retirado en la 3.0.0) (el bloque «Te llevas de regalo» de la ficha) y, para la
     landing, `kpypromolanding` con el tipo `regalo` leído como «Regalo»;
   - `upgrade-2.1.0.php`: el regalo como **tarjeta** de la ficha: `regalo_corto`, la tabla
     `kpy_coupon_regalo_eleccion`, los hooks `actionDuplicateCartData` («pedir de nuevo») y
     `displayCartExtraProductActions` (lo que dice cada línea de la cesta; el módulo, el primero) y el
     reparto otra vez. Iba con `kpyproductbuy` 1.3.0 (retirado en la 3.0.0) y `kpypromolanding` 1.2.0;
   - `upgrade-2.2.0.php`: el regalo **por unidades**: las columnas `unidades` de
     `kpy_coupon_regalo_eleccion` (las elecciones que ya hubiera pasan a toda su línea) y de
     `kpy_coupon_regalo_cesta`, y los hooks `actionCartUpdateQuantityBefore` y `actionPresentCart`.
     Iba con `kpyproductbuy` 1.4.0 (retirado en la 3.0.0). Se puede ejecutar más de una vez;
   - `upgrade-2.3.0.php`: **por cantidad**: las columnas `cupo`, `cupo_agotado`, `cupo_ficha` y
     `etiqueta` de `kpy_coupon` (el `regalo_tope` que hubiera pasa a `cupo`), las tablas
     `kpy_coupon_cupo_cesta`, `kpy_coupon_cupo_pedido` y `kpy_coupon_precio_cesta` (los precios de
     cesta de las elecciones se mudan ahí), el hook `actionValidateOrderBefore` y el reparto otra
     vez. Iba con `kpyproductbuy` 1.5.0 (retirado en la 3.0.0) y `kpypromolanding` 1.2.1. Se puede ejecutar más de una vez;
   - `upgrade-2.4.0.php`: el cupo **por artículo** y «Outlet»: `kpy_coupon.cupo_lista`,
     `kpy_coupon_scope.unidades` y `kpy_coupon_scope.agotado`, y el reparto otra vez. Sin hooks
     nuevos. Iba con `kpyproductbuy` 1.5.1 (retirado en la 3.0.0) y `kpypromolanding` 1.3.0 (el cajón «Outlet y
     liquidación»). Se puede ejecutar más de una vez;
   - 2.4.1 (20/10/2026): sin `upgrade-*.php`, solo el formulario (`pantalla.tpl` y `admin.css`): el
     cupo en su recuadro, «Encendida» aparte, las tarjetas del paso 1 alineadas arriba y el selector
     de la lista en su línea con el botón (la hoja del back office, `.bootstrap .form-control`, pesa
     más que una clase sola). El `upgrade` solo apunta la versión. Va con `kpypromolanding` 1.3.1 y
     `kpysupply` 1.0.1 (sus pasos, también más claros);
   - `upgrade-3.0.0.php` (23/10/2026, la generación **sin dependencias**; la 2.5.0 no existió): la
     ficha la pinta este módulo (`KpcFicha`): registra `displayProductPriceBlock`,
     `displayProductAttributeFlags`, `actionPresentProduct`, `actionFrontControllerSetVariables` y
     `displayCartModalFooter`; crea las filas de los hooks propios con título y descripción
     (`HOOKS_PROPIOS`) y registra los de consulta que contesta (`actionKpyCouponsProductosConPromo`,
     `actionKpyCouponsAlcance`, `actionKpyCouponsPromociones`); quita `actionKpyProductBuyCoupon`;
     fija las posiciones por la API del core (`colocarHooks`); las etiquetas se calculan por hook
     (`KpcFlags`) y las filas viejas en las tablas de `kpyproductflags` **solo se cuentan** (las borra
     el SQL del paquete o `bin/limpiar-flags.php --borrar`); el reparto otra vez. `bin/flags-promos.php`
     desaparece. Va con `kpypromolanding` 1.4.0, `kpysupply` 1.1.0 y `kpycrosssell` 1.6.0;
     `kpyproductbuy` se desinstala. Se puede ejecutar más de una vez.
3. Comprobar los hooks (`Kpycoupons::HOOKS`): `actionCartSave`,
   `displayShoppingCartAfterDetailedTotals`, `actionFrontControllerSetMedia`,
   `actionFrontControllerInitAfter`, `actionProductFlagsModifier` (**después** de `kpygifts`,
   `kpyproductflags` y `kpyspecialprices` y **antes** de `kpypromolanding`), `actionDoofinderExtendFeed`,
   `actionValidateOrder`, `actionPresentCartProduct`, `actionPresentProductListing`,
   `actionPaymentModuleProductVarTplAfter`, `actionDuplicateCartData`,
   `displayCartExtraProductActions` (kpycoupons el primero, antes del «Recíbelo entre…»),
   `actionPresentCart`, `actionCartUpdateQuantityBefore`, `actionValidateOrderBefore`,
   `displayProductPriceBlock`, `displayProductAttributeFlags` (detrás de `kpygifts`),
   `actionPresentProduct` (detrás de `kpyspecialprices`), `actionFrontControllerSetVariables`,
   `displayCartModalFooter`, `actionKpyCouponsProductosConPromo`, `actionKpyCouponsAlcance` y
   `actionKpyCouponsPromociones`. La pantalla Kompy · Promociones avisa si el orden no es ese
   (`posicionesMal`).
4. Comprobar Catálogo → **Kompy · Promociones**, y vaciar la caché.

Necesita `kpyspecialprices` para el Special Price (si falta, la pantalla lo dice y no deja crearlo).
No necesita ningún otro módulo y no llama a ninguno; sin `ficha.js` el cupón sigue funcionando escrito
en la cesta y la opción del regalo viaja al añadir.
