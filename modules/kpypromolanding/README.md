# kpypromolanding · Landing de promociones

`/promociones` y todo lo que cuelga de ahí, las **categorías automáticas** de cada promoción, el
bloque de promociones vivas en la **landing de cada marca**, el **paso 6** del formulario de cada
promoción y las **campañas** (`/blackfriday`) como capa por encima de toda la tienda.
Versión 1.4.0 (23/10/2026), para PrestaShop 8 y 9, PHP 8.

No crea promociones: las de `kpycoupons` (Catálogo → Kompy · Promociones), que es donde se crean,
se las **pregunta por su hook de consulta** `actionKpyCouponsPromociones` (`kpycoupons` **3.0.0** o
posterior), y kpycoupons le avisa por `actionKpyCouponsReparto`, le pide el paso «En la landing»
por `displayKpyCouponsFormulario`/`actionKpyCouponsGuardar`, la capa de campaña de la ficha por
`actionKpyCouponsCampana` y deja repasar la cesta por `actionKpyCouponsResumen`. **Ningún módulo
llama a funciones de otro ni lee sus tablas** (regla de Julián, 23/10/2026): todo va por hooks, con
datos curados; ver «Hooks». Sin kpycoupons encendido, la landing no sincroniza nada (y lo dice).

## Qué hace, en corto

Cuando `kpycoupons` rehace el reparto (al guardar una promoción, y en la primera visita después de
que una empiece o termine), este módulo:

1. Calcula la **clave** de cada promoción: `{marca}-{tipo}[-{mascota}]`
   (`royal-canin-special-price-perro`, `boske-special-price`, `eukanuba-cupon-perro`). La mascota
   solo va si todos sus productos son de una. Varias marcas: `promo-{codigo}`. En el paso 6 de la
   promoción se puede poner otra a mano.
2. Crea (la primera vez) o reutiliza la **categoría** de esa clave bajo una raíz «Promociones»
   (debajo de Inicio), mete sus productos (los del reparto, solo activos y visibles), la enciende si
   tiene algo y la apaga si no. **Nunca borra una categoría**: las direcciones de emails y anuncios
   siguen contestando. Tres categorías permanentes por reglas: `kilos-gratis` (formatos a la venta
   cuyo nombre dice «gratis»), `regalos` (packs con regalo, `kpy_packs.is_gift`) y `outlet` («Outlet
   y liquidación»: formatos cuyo nombre dice «outlet» y, desde la 1.3.0, las liquidaciones y outlets
   de `kpycoupons`, de todas las marcas).
3. Recorta **sin fondo** el logo de la marca y el saco héroe de la promoción (el de perro y el de
   gato, cada uno por su lado y apilados en el banner) y los guarda en `img/kpypromo/` (biblioteca,
   `kpy_promo_imagen`). Ver «Imágenes».
4. Decide la **foto de cada producto en las tarjetas** de la landing: la primera de su ficha que es
   un solo saco de pie (`KplHeroes`, tabla `kpy_promo_foto`). Ver «Imágenes».
5. Deja escrito el **resumen** de la landing (`kpy_promo_dato`): tipos con sus cuentas por mascota,
   marcas por tipo, banners con sus textos e imágenes (también por mascota), lo más rebajado; por
   producto, sus promociones vivas por tipo (para las etiquetas de campaña); y la foto de cada
   tarjeta. Las páginas solo leen eso.

## El paso 6, «En la landing» (en el formulario de Kompy · Promociones)

Lo pinta este módulo al final del formulario de cada promoción (hook `displayKpyCouponsFormulario`)
y lo guarda al guardarla (`actionKpyCouponsGuardar`, antes del reparto, para que la sincronización
ya lo encuentre). Todo opcional, en blanco = automático: si sale en la landing (`listar`), la
dirección a mano (`clave_manual`), el título y el texto del banner, la foto (automática, un producto
por su id, o una imagen subida a mano de la biblioteca), el orden y en qué campañas entra. Enseña
cómo sale hoy su banner (logo y sacos) y su estado. Se guarda en `kpy_promo_promo`. Solo escribe
si el formulario traía el paso (`kpl_paso6`); con el módulo apagado, el formulario de `kpycoupons`
es el de siempre.

## Direcciones

| Dirección | Qué |
|---|---|
| `/promociones` | Portada: Perros/Gatos, tipos con algo este mes, banners marca a marca, lo más rebajado, accesos. Con una campaña viva que lo pide, manda (302) a la campaña. (El boletín está fuera desde la 1.4.0: ver «Hooks», «Lo que ya no hace») |
| `/promociones/{tipo}` y `/promociones/{tipo}/{perros\|gatos}` | Un tipo (`special-price`, `cupones`, `segunda-unidad`, `kilos-gratis`, `regalos`, `outlet`): marcas con su rebaja y todos sus productos |
| `/promociones/{clave}` | Una promoción: banner, duración, filtro perro/gato y sus productos. Terminada o programada: lo dice y enseña las de ahora (200, `noindex`) |
| `/promociones/todas` | Todo lo que está en promoción |
| `/{campaña}` (`/blackfriday`) | La portada con la piel y los textos de la campaña: antes («empieza el…»), durante (sus promociones, su lema en cada banner, el orden de marcas que se quiera) y después («ha terminado»). Vive todo el año |
| `?mascota=perro\|gato` | El filtro de mascota en cualquiera; `?kpc=CODIGO` deja un cupón puesto (lo hace `kpycoupons` al iniciar la petición: es un contrato, ver «Hooks») |

Los listados son listados de PrestaShop (`ProductListingFrontController` + un proveedor propio,
`KplBuscador`): la misma tarjeta, el mismo orden y paginación por ajax que una categoría. El orden
por defecto es «mayor descuento».

## Imágenes: de dónde salen y cómo se recortan

**Qué foto.** De cada ficha se prueban, por este orden, la foto propia del formato preferido (el saco
Jirafa en kilos gratis: la foto que está en ese formato y en ningún otro; el pack en regalos), la
portada y el resto por su orden, y se queda la primera que es **un solo saco de pie** (un objeto, más
alta que ancha). Una foto de transición (saco viejo, flecha, saco nuevo) tiene 2 o 3 objetos y se
descarta sola; un rótulo o una lata solo se usa si no hay nada mejor. La decisión queda apuntada en
la biblioteca (`objetos`, `apta`) y cada foto se recorta una sola vez (por la firma del original).
En regalos, al lado del saco va el producto que se regala. Si la promoción es de perros y gatos, el
héroe de cada mascota se guarda por separado (`imagenes_perro`, `imagenes_gato` en el resumen): con
el filtro de perros o gatos, las tarjetas de tipo, los banners y la cabecera enseñan el saco de esa
mascota; sin filtro, los dos apilados.

**La foto de las tarjetas.** Las tarjetas de producto (lo más rebajado, los listados por tipo y por
promoción, la campaña) enseñan la portada del catálogo… salvo que sea una foto de transición: la
misma regla de arriba, sin recortar, decide la primera foto de la ficha que es un solo saco de pie.
Cada foto se **mide** una vez (`KplRecorte::medir()`: los pasos 1 a 3 y 5 del recorte, en una copia
de 420 px, 0,3 s) y la medida queda en `kpy_promo_foto` con la firma del fichero; si la biblioteca
ya recortó esa foto, vale su decisión. Al sincronizar se escribe, por producto, la foto elegida
(`kpy_promo_dato`, `heroes`), y el hook `actionPresentProductListing` cambia la portada de la
tarjeta al pintarla (si la foto buena es de otro formato que el de la tarjeta, se piden todas las de
la ficha). No se escribe nada en el catálogo: la ficha y las categorías de la tienda siguen con su
portada, salvo que en la pantalla se marque «también en toda la tienda» (`KPL_HEROE_TIENDA`).
Probado con 25 fotos reales: las 6 de transición de Eukanuba descartadas (2–3 objetos o más anchas
que altas), los sacos blancos de Royal Canin y Advance aptos.

**Con qué motor**, por este orden, y si uno falla el siguiente:

1. **rembg** (https://github.com/danielgatis/rembg, inteligencia artificial, gratis): un contenedor
   junto a la tienda, en la misma red que PHP:
   `docker run -d --name rembg --restart unless-stopped -e OMP_NUM_THREADS=4 -e U2NET_HOME=/models -v rembg-models:/models -p 127.0.0.1:7000:7000 danielgatis/rembg s --host 0.0.0.0 --port 7000`
   (antes, `docker run --rm -e U2NET_HOME=/models -v rembg-models:/models danielgatis/rembg d u2net`
   descarga el modelo). En la pantalla: dirección `http://rembg:7000`, modelo `u2net`, «Probar y
   guardar». 1,5–3 s por foto. El modelo va como campo del formulario en el POST.
2. **remove.bg** (servicio de pago, clave en la pantalla; la cuenta gratuita da 50 fotos al mes a
   0,25 megapíxeles, de sobra para los banners).
3. **Recorte propio** (`KplRecorte` v3, sin dependencias): inundación desde el borde con tolerancia
   estricta (la línea del contorno de un saco blanco es una pared), sellado morfológico de las
   entradas, contorno que respeta los bordes de un producto blanco, sombra de la foto difuminada y
   recuento de objetos. Probado con 25 fotos de catálogo (sacos blancos incluidos) y 11 logos.
   `bin/recortar.php` lo ejecuta en consola.

Los **logos** van siempre con el propio (a la inteligencia artificial se le van las letras finas).
Las tres salidas pasan por el mismo recorte de aire, escalado y recuento de objetos.

**Lo que se sube a mano** (la biblioteca de la pantalla, 1.1.1). Cada imagen subida lleva su
**tipo** (logo, saco, lata u otra foto), su marca, su mascota (perros, gatos o las dos) y, si se
quiere, su **gama** (Mature, Puppy…). Qué subir: PNG o WebP con el fondo transparente, recortado al
borde; logos de 600 px de ancho o más, sacos y latas de 900 o más (el módulo lo deja a esa medida,
sin aire y en WebP; no agranda). Un JPG o un PNG con fondo blanco se recorta como las automáticas
(los logos con el propio; lo demás con el motor que haya). Qué hace cada una: un **logo** sustituye
al automático de su marca en todas partes (`KplBiblioteca::logo()` lo mira primero); un **saco** o
una **lata** de una marca es el héroe de los banners de sus promociones en vez de la foto del
catálogo (`KplBiblioteca::subida()`, desde `KplSincronizar::imagenesDe()`): la de la mascota gana a
la de «perros y gatos», y una con gama solo entra en las promociones que la nombran (el nombre de la
promoción en Kompy · Promociones, el título del banner o los nombres de sus productos) y entonces
gana a las que no la llevan; «otra foto» solo se usa si se elige en el paso 6. Al subir un logo o un
saco se sincroniza en el acto. Si se borra una subida, vuelve la automática. Columnas `tipo` y
`gama` en `kpy_promo_imagen`. El estándar está escrito en la propia pantalla.

**El tamaño de los logos.** Un logotipo muy apaisado (Eukanuba, 9:1) a la misma altura que uno
casi cuadrado (Alpha Spirit, 1,6:1) parece diminuto: se iguala el área. `KplBiblioteca::factorLogo()`
da un factor (raíz de 2,5 entre la proporción, entre 0,55 y 1,35) que se guarda en el resumen
(`logo_k`), las plantillas lo ponen en `--kpl-logo-k` y el CSS multiplica por él la altura de cada
sitio (banners, tarjetas de marca, cabecera de los listados, bloque de la portada).

## Campañas (Black Friday…)

Una campaña es **una capa por encima** de las promociones de siempre, con fecha de inicio y de fin,
que no toca nada de lo que hay debajo y se decide por la hora de cada visita (nada que arrancar ni
que parar, nada que limpiar después):

- su dirección (`/blackfriday`) con su piel (negra: fondo negro, tarjetas gris oscuro, texto blanco,
  amarillo y rojo en etiquetas y rebajas), su título, su texto y su lema («Stock limitado · hasta
  agotar existencias») en la portada y en cada banner;
- antes de empezar, «empieza en N días» con las promociones de ahora; después, «ha terminado» con
  las promociones de ahora;
- mientras está viva, `/promociones` manda a ella (si se marca), una **cinta** arriba de todas las
  páginas lleva a ella, y las **etiquetas** de las promociones que entran cambian de nombre y color
  en toda la tienda (listados, buscador, ficha): «Special Price» → «Black Friday −10 %», «Código
  −10 %» → «Cupón Black −10 %», «2ª ud. −30 %» → «2ª ud. Black −30 %», «kg Gratis» → «kg gratis ·
  Black». Nombre y colores, editables por tipo. Se cambian **al pintarlas** (hook
  `actionProductFlagsModifier`, el último después de `kpycoupons`): no se escribe nada en las tablas
  de etiquetas y al terminar vuelven solas. El tema (`product-flags.tpl`) pinta el **fondo** que le
  llega en el `style` de la etiqueta pero no el color del texto, y lo fija en blanco con
  `!important` dentro de una capa CSS (`@layer`), que gana a cualquier hoja de estilos normal: por
  eso el módulo mete el color del texto dentro del valor del fondo (`#111111 !important;color:#F2C965`
  → el estilo en línea de la etiqueta lleva el fondo y el texto) y añade la clase
  `kpl-camp--{tipo}`; la cinta (`cinta.tpl`, en todas las páginas) lleva además un `<style>` con
  el color por clase, para otras plantillas. Un Special Price puesto a mano en el catálogo (que no
  es una promoción de Kompy · Promociones) entra cuando entra todo, con el % de su precio («Black
  Friday −50 %»);
- las **páginas de la landing** (por tipo, por promoción, todas y `/promociones` si no manda a la
  campaña) llevan su piel y sus nombres: la cabecera (nombre, filtro y marcas) es un bloque negro,
  «Black Friday: el precio ya rebajado en la ficha», «Cupón Black: el descuento, en la cesta», el
  lema con los días que quedan, las tarjetas de marca y de tipo con la etiqueta de campaña, y los
  títulos automáticos («Special Price: −10 % en toda la gama Boske») con el nombre de la campaña
  (uno escrito a mano en el paso 6 se queda como está). El listado sigue en blanco. Los colores de
  las etiquetas llegan en variables CSS en el propio bloque (`KplCampanas::estilo()`), así que si
  en la pantalla se cambia un color, toda la landing lo sigue;
- también mientras está viva, la capa llega al resto de la tienda, siempre al pintar y sin escribir
  nada: un **bloque en la portada de la tienda** (`displayContentWrapperTop`, solo en la portada:
  título, lema con los días que quedan, accesos por tipo y los logos de las marcas de la campaña, a
  dos columnas; si la campaña lo pide (`sin_carrusel`, marcado por defecto), el carrusel grande de la
  portada no sale y el bloque hace de portada; las dos semanas antes, una línea con cuándo empieza);
  el **precio Special Price de las tarjetas** en los colores de la etiqueta (un `<style>` con la
  cinta); la **ficha** (`actionKpyCouponsCampana`, hook de consulta que dispara `kpycoupons` 3.0.0 al
  pintar la ficha y la cesta: le devolvemos el nombre y los colores de campaña por tipo, y kpycoupons
  los pone en el bloque del cupón o de la 2ª unidad, en el chip de cada formato y en la etiqueta del
  precio); la **cesta** (`actionKpyCouponsResumen`:
  «Cupón Black −10 % · EUKA10», «Black Friday en 2 productos», con los colores de la etiqueta); y
  el **bloque de cada marca** con la piel de la campaña, su nombre y su lema;
- qué promociones entran: las marcadas con la campaña en el paso 6 (`kpy_promo_promo.campanas`); si
  ninguna lo está, todas las vivas;
- el orden de las marcas en su portada y en el bloque de la portada de la tienda: nombres separados
  por comas (las demás detrás, por rebaja).

**Las tres predefinidas** se crean al instalar y al actualizar (las que falten, por clave; también
con el botón de la pantalla), con sus fechas de cada año, sus textos y su **paleta**
(`KplCampanas::predefinidas()`):

| Campaña | Dirección | Fechas | Piel | Paleta (fondo · acento · secundario) |
|---|---|---|---|---|
| Black Friday | `/blackfriday` | 1–30 de noviembre | oscura | negro #111111 · amarillo Kompy #F2C965 · coral #FF6A5C |
| Cyber Monday | `/cybermonday` | el lunes después del cuarto jueves de noviembre y el día siguiente (48 h; en 2026, 30/11–1/12) | oscura | azul noche #0E1430 · turquesa eléctrico #3DE1D2 · magenta #FF5FB4 |
| Reyes Magos | `/reyes` | del 26 de diciembre a la noche del 5 de enero | clara | marfil #FFF8EC · púrpura real #6A3FB5 · oro #D9A82E |

Cada paleta va a juego en toda la tienda y no se parece a la anterior: el Black Friday es el de
siempre; el Cyber Monday es oscuro pero no negro (lo «cyber»); Reyes es clara, para descansar del
negro, con los colores de la fiesta y el regalo. Si dos coinciden en fecha (el Cyber Monday cae en
el último día del Black Friday), manda la que empezó más tarde.

**La paleta** son tres colores por campaña (`colores`: fondo, acento, acento secundario) y de ellos
sale todo (`KplCampanas::derivados()`): las superficies de las tarjetas, los bordes, el brillo de la
cabecera, los tintes de cada tipo, el texto de apoyo, y las etiquetas de campaña (en la piel oscura,
fondo de la campaña y texto del acento; en la clara, llenas del acento con el texto claro u oscuro
según el color; un acento muy claro, como el oro, se oscurece cuando va como texto). Los colores
llegan a la landing, a la cabecera de los listados, al bloque de cada marca y al de la portada de la
tienda como variables CSS en el propio bloque (`estilo()`, `estiloPortada()`); a la ficha, la cesta y
las etiquetas de las tarjetas, por las etiquetas de campaña; a la cinta, directos. En la pantalla
hay tres selectores de color y las tres paletas preparadas de un clic; al cambiar la paleta, las
etiquetas se recalculan solas y después se afinan a mano si se quiere. Piel oscura (fondo oscuro,
texto claro) o clara (fondo claro, texto oscuro): en la oscura las etiquetas y píldoras van llenas
de color con el texto oscuro, nunca oscuro sobre oscuro, y el gris de apoyo es claro (#D5DBDE):
contrastes repasados el 14/10. Y con matices (Rafa: «la veo muy negra»): la cabecera con un brillo
del acento, una raya fina antes de cada sección, las tarjetas en una superficie algo más clara con
borde y sombra y un tinte del color de su tipo (el acento en Special Price y kilos gratis, el
secundario en cupón y 2ª unidad, blanco en outlet), el banner destacado con un filo del acento.

**La portada de la tienda** durante una campaña (`displayContentWrapperTop`): el bloque de la
campaña con el ancho del carrusel (1140 px, el contenedor de la tienda), y debajo sus **productos
con más descuento** (`KplPromos::portada()`): los que entran en la campaña, con las marcas
prioritarias primero (el orden de marcas de la propia campaña y después las «marcas primero» de la
pantalla, por defecto Boske, el buque de la web), dentro de cada marca por rebaja, y cada marca pone
como mucho la mitad para que no salga todo de la misma; cuántos, en la pantalla (por defecto 8;
0 = ninguno). Con la foto de las tarjetas. En el móvil, los accesos por tipo y los logos van en una
fila que se desliza, y la fecha de la píldora no sale (el lema ya dice cuánto queda). Con la cinta
puesta no sale la banda de siempre de arriba («Envíos gratis…»): una sola banda, no dos.

En el móvil la cabecera del tema va fija y reserva su alto en `--kpy-menu-mobile-height` (que
también mide la barra y el panel del menú); con la cinta dentro mide 34 px más (la cinta va en una
sola línea), así que la cinta añade un `<style>` que baja el contenido y el panel del menú esos
34 px. Sin eso, la cinta tapaba el principio de cada página en el móvil.

## Hooks

Regla (23/10/2026, con `kpycoupons` 3.0.0): **ningún módulo llama a funciones de otro ni lee sus
tablas**. Lo que este módulo sabe de kpycoupons le llega por hooks, con datos curados (filas
públicas de claves fijas, nunca filas crudas ni objetos), y lo que kpycoupons sabe de la landing,
igual. Quien dispara un hook lo nombra, lo crea y documenta sus parámetros; el que contesta no
depende del que dispara. Sin `Module::getInstanceByName` ajeno, sin `class_exists` de clases ajenas,
sin `require` de ficheros ajenos.

### El que dispara (consulta; lo contesta kpycoupons)

- `actionKpyCouponsPromociones` (`KplPromociones`):
  `Hook::exec('actionKpyCouponsPromociones', ['id_shop' => N], null, true)` → `$r['kpycoupons']`:
  por `id_kpy_coupon`, la **fila pública** de todas las promociones de la tienda, encendidas o no:
  `id_kpy_coupon`, `codigo`, `nombre`, `tipo` (cupon | segunda_unidad | special_price | regalo),
  `porcentaje`, `activo`, `fecha_desde`, `fecha_hasta` (`Y-m-d H:i:s` o null), `etiqueta` ('' |
  liquidacion | outlet | bajada), `estado` (viva | programada | terminada | agotada | apagada: lo
  calcula kpycoupons, una sola definición para todos), `cajon` ('' | liquidacion | outlet),
  `regalo_clase` (kilos | regalo), `auto_web`, `en_ficha`, `cupo`, `cupo_lista`, `cupo_agotado`,
  `id_manufacturer` (el de su ámbito de marca), `ambitos` [{tipo, id, valor, unidades, agotado}],
  `items` {clave ⇒ id_product} (el reparto: la clave es `id_product_attribute`, o −id_product sin
  combinaciones) y `parejas` [{clave, id_product, r_id_product, r_ipa, n}] (regalo). Se pregunta una
  vez por sincronización y una por petición en la pantalla (caché estática). Si **nadie contesta**
  (kpycoupons apagado, o anterior a la 3.0.0), la sincronización no toca nada, lo apunta en el
  registro y la pantalla lo avisa; una lista vacía de verdad sí vacía las categorías. De la fila se
  usa todo lo que antes se copiaba en SQL: estado (agotada o apagada = terminada en la landing),
  cajón, clase del regalo, parejas (los tamaños «15 kg + 3 kg» y el producto que se regala),
  artículos, ámbitos (la marca de la clave), % y código. Lo que no va en la fila pública no se
  inventa: `tamanos` (los tamaños elegidos, «saco 12 kg») no va en la 3.0.0, así que el título
  «−10 % en sacos de 8 a 15 kg» solo saldrá si kpycoupons lo añade a `KpcContrato::promocion()`.

### Los que contesta (registrados al instalar, `HOOKS`)

- `actionKpyCouponsReparto` (lo dispara kpycoupons al final de cada reparto; params `version`,
  `id_shop`, `items`, `avisos`, `alcance`, `promociones` (filas públicas), `guardada`): sincroniza todo
  (`KplSincronizar::todo`). Solo usa `id_shop`: lo demás lo vuelve a preguntar por el hook de
  consulta, ya con el reparto escrito.
- `displayKpyCouponsFormulario` (params `version`, `id` (0 = nueva), `valores`, `duplicar`, `paso`,
  `url_editar`, `url_ver`): el paso «En la landing», con el número que manda `paso` (kpycoupons los
  cuenta módulo a módulo; el nuestro suele ser el 6).
- `actionKpyCouponsGuardar` (params `version`, `id`, `valores`, `promocion`): guarda lo del paso en
  `kpy_promo_promo`, solo si el formulario traía `kpl_paso6`.
- `actionKpyCouponsCampana` (consulta: lo dispara kpycoupons al pintar la ficha y la cesta, con
  `array_return`; params `id_product`, `id_product_attribute`, `tipos` (los tipos en pantalla: cupon,
  segunda_unidad, special_price)): null, o `{nombre, clave, piel, tipos: {tipo: {label, background,
  color}}}` solo con los tipos pedidos. Sustituye a `actionKpyProductBuyCapa` (el hook del módulo de
  la ficha, retirado en la 1.4.0; `upgrade-1.4.0.php` registra el nuevo y quita el viejo).
- `actionKpyCouponsResumen` (param `resumen`, por referencia): los nombres y colores de campaña en
  la cesta («Cupón Black −10 % · EUKA10», «Black Friday en 2 productos»).
- `actionProductFlagsModifier`: las etiquetas de campaña (solo con campaña viva), el último después de
  kpycoupons. Reconoce las de kpycoupons por su campo **`kpc`** = `{tipo: cupon | segunda_unidad |
  regalo | special_price, id_kpy_coupon, pct}` (también en la `kpy_special_price` de
  `kpyspecialprices`, que kpycoupons viste); de respaldo, la clave `kpy_special_price`, el `type`
  (`kpc-cupon`, `kpc-segunda`, `kpc-regalo`, `kpc-liquidacion`, `kpc-outlet`, `kpc-bajada`,
  `kpc-especial`) y el texto «kg Gratis» de la etiqueta del catálogo. Un regalo entra como «Kilos
  gratis» o «Regalos» según lo que regala (lo dice el resumen por `id_kpy_coupon`).
- `moduleRoutes`: las direcciones de arriba (la de cada campaña activa, leída de `KPL_CAMPANAS`).
- `actionFrontControllerSetMedia`: la sincronización pendiente tras instalar (después de contestar
  a la primera visita, porque en consola no hay dominio para las direcciones de las imágenes); la
  hoja de estilos en la landing de marca. Las hojas se registran con la fecha del fichero como
  versión (`promos.css?202610141601`): al desplegar una nueva, el navegador no se queda con la que
  tenía guardada (Rafa vio la ficha con la hoja vieja el 14/10).
- `actionPresentProductListing`: la foto de las tarjetas (en las páginas de la landing; en toda la
  tienda si se marca).
- `displayKpyPromoMarca`: el bloque de promociones vivas de una marca. Lo llaman dos plantillas de
  `kpymanufacturer` (parche en `entregas/parche-promociones-menu-y-marcas-2026-10-14`): en la
  genérica sustituye al slider de banners fijos mientras haya promoción; en la de Boske, a la
  sección fija. Sin promoción devuelve vacío. Con campaña viva, su piel.
- `displayBanner`: la cinta de campaña, el color del texto de las etiquetas de campaña y el estilo
  del precio en las tarjetas (solo con campaña viva).
- `displayKpyPromoMenu`: el botón «Ofertas y promociones» del menú mientras hay campaña viva, con
  su nombre y sus colores (`KplCampanas::coloresMenu()`: piel negra, el fondo con el acento encima;
  clara, el acento relleno) y su dirección. Lo llama la plantilla parcheada de `kpymenu` (PC y
  móvil, `movil=true`); sin campaña devuelve vacío y la plantilla pinta el botón de siempre.
  `kpymenu` pinta su menú con `fetch()` sin cache_id, así que el hook se evalúa en cada visita.
- `displayContentWrapperTop`: el bloque de campaña en la portada de la tienda (solo allí).

### Otros contratos con kpycoupons (datos, no llamadas)

- La **cookie** de PrestaShop `kpy_cupon`: `CODIGO` = el cupón que el cliente lleva puesto;
  `!CODIGO` = lo quitó a mano; vacía = ninguno. La página de una promoción de cupón la lee del
  contexto (`KplPromos::cuponPuesto()`) para decir «✓ Cupón EUKA10 aplicado» o pintar «Aplicar cupón».
- **`?kpc=CODIGO`** en cualquier dirección deja el cupón puesto (kpycoupons lo hace en
  `actionFrontControllerInitAfter`, antes de que nuestras páginas se preparen: la cookie ya está
  escrita cuando se lee). Los banners y la cabecera enlazan así («Aplicar cupón EUKA10»).
- Las **etiquetas**: el campo `kpc`, los `type` `kpc-*` y la clave `kpy_special_price`, como arriba.

### Lo que ya no hace (1.4.0)

- `Module::getInstanceByName('kpycoupons')->elegido()` (ahora la cookie), `Module::getInstanceByName(
  'ps_emailsubscription')->newsletterRegistration()` (ver abajo), `actionKpyProductBuyCapa`
  (el módulo de la ficha se retira), las hojas de `kpyspecialprices` y `kpyproductflags` en nuestras páginas
  (lo poco que las tarjetas necesitan va copiado en `promos.css`, con su origen) y las lecturas de
  `kpy_coupon`, `kpy_coupon_scope`, `kpy_coupon_item` y `kpy_coupon_regalo` (tres copias de la
  semántica viva/terminada/agotada/cajón que había hasta la 1.3.1: todo por el hook).
- **El boletín**: la tarjeta «¡No te suscribas!» de la portada llamaba a `ps_emailsubscription` para
  hacer el alta. Su widget (`{widget name='ps_emailsubscription'}`) no sirve en nuestras páginas: la
  plantilla del tema para ese bloque (`themes/kpy_theme/modules/ps_emailsubscription/…/ps_emailsubscription.tpl`)
  solo pinta si `$page.page_name` está en una lista fija (`product`, `category`, `index`, `cart`,
  `kpybrands`, `kpylanding`…) y en las nuestras (`module-kpypromolanding-*`) devuelve vacío. Así que
  el boletín está **fuera de la landing** hasta que Julián decida (pregunta 15 del plan): o añade
  nuestras páginas a esa lista (y ponemos el widget), o acepta que nuestro formulario envíe al
  controlador público del módulo (`module/ps_emailsubscription/subscription`, lo mismo que hace su
  propio JavaScript, sin llamar a ninguna función), o se queda sin boletín. La hoja conserva los
  estilos `.kpl-news*` por si vuelve.

## Tablas y ficheros

`kpy_promo_categoria` (clave → categoría), `kpy_promo_promo` (lo nuestro de cada promoción: clave,
y lo del paso 6), `kpy_promo_imagen` (biblioteca, con `objetos` y `apta`, y `tipo` y `gama` en las subidas a mano), `kpy_promo_foto` (la
medida de cada foto del catálogo: objetos, caja, apta), `kpy_promo_dato` (resumen, productos por
promoción y la foto de cada tarjeta, JSON), `kpy_promo_campana` (campañas). Ficheros en
`img/kpypromo/`. Configuración: `KPL_RAIZ`, `KPL_PENDIENTE`, `KPL_REMBG_URL`, `KPL_REMBG_MODELO`,
`KPL_REMOVEBG_KEY`, `KPL_CAMPANAS`, `KPL_HEROE_TIENDA`, `KPL_FEATURE_MASCOTA`/`KPL_VALOR_PERRO`/
`KPL_VALOR_GATO` (la característica «Mascota», por nombre), `KPL_ACCESO_1`/`KPL_ACCESO_2` (las
categorías de los accesos de abajo, por nombre).

Fuera de eso solo escribe en las categorías que son suyas (`category*` bajo la raíz) y en
`category_product` de esas categorías. Nada de core, nada de override, nada en las tablas de
etiquetas ni en el catálogo. `kpy_promo_categoria.pct` (1.4.0) es el % mayor de las promociones vivas
de cada clave, escrito al sincronizar: con él ordena `KplBuscador` («mayor descuento primero») sin
cruzar nada de kpycoupons en cada visita.

**Qué lee de qué tabla.** De `kpycoupons`, **ninguna** (todo por `actionKpyCouponsPromociones`).
Del catálogo de PrestaShop, solo lectura: `product`, `product_shop`, `product_lang`, `manufacturer`,
`product_sale`, `feature_product`, `feature*` (la característica «Mascota», por nombre),
`product_attribute`, `product_attribute_combination`, `attribute_lang`, `category*`, `image*`. De los
módulos de Julián, dos lecturas declaradas y protegidas por existencia de tabla o columna:
`kpy_packs` (`id_product_pack`, `id_product_item`, `is_gift`: los packs con regalo de la categoría
permanente «Regalos» y el producto que se regala) y `kpy_product_attribute.active` (un formato
apagado no cuenta en las permanentes).

## Pantalla

Catálogo → **Kompy · Landing de promociones**, ordenada como se usa (rehecha el 14/10 a petición de
Rafa, con la misma pinta que Kompy · Promociones):

0. **Cómo funciona**: los tres pasos (se crea en Promociones → esto se hace solo → aquí se mira y se
   afina) y las cifras (última sincronización, promociones en la landing, programadas, categorías,
   imágenes, campaña en marcha), con «Ver /promociones» y «Sincronizar ahora».
1. **Las promociones en la landing**: cada promoción de Kompy · Promociones con su estado, su banner
   (logo y sacos), su dirección y categoría, lo afinado en su paso 6 y el botón «Editar» (que abre
   la promoción allí). Debajo, las tres categorías de siempre (kilos gratis, regalos, outlet).
2. **Campañas**: la lista y el formulario por pasos (qué y cuándo; cómo se ve, con la piel a elegir;
   en toda la tienda: cinta, redirección y etiquetas con muestra; orden de marcas).
3. **Imágenes**: el motor de recorte (plegado) y la biblioteca: qué subir y qué hace cada cosa, el
   formulario (imagen, tipo, marca, mascota, gama, nombre), las subidas a mano con su tipo y, plegadas,
   las hechas solas (logo, saco héroe o descartada), con cuántas fotos se han medido para las tarjetas.
4. **Mantenimiento** (plegado): rehacer las imágenes, la foto de las tarjetas también en toda la
   tienda, borrar todo.

## Instalar, desinstalar, borrar

- Instalar: copiar a `modules/`, `kpycoupons` a la **3.0.0** o más (el hook de consulta
  `actionKpyCouponsPromociones` y `actionKpyCouponsCampana`; con una anterior el módulo se instala
  pero no sincroniza nada y la pantalla lo avisa; el módulo de la ficha ya no hace falta: se retira),
  `bin/console prestashop:module install kpypromolanding` (después de `kpycoupons`, para que su hook
  de etiquetas vaya el último), parches de `kpymenu` y `kpymanufacturer`, vaciar caché. La primera
  visita a la tienda completa las imágenes. Si hay contenedor rembg, poner su dirección en la
  pantalla antes de «Rehacer las imágenes».
- Actualizar: `bin/console prestashop:module upgrade kpypromolanding`
  (`upgrade-1.1.0.php`: la tabla `kpy_promo_foto`, los hooks nuevos y una sincronización pendiente;
  `upgrade-1.1.1.php`: el hook del menú y las columnas `tipo` y `gama` de la biblioteca;
  `upgrade-1.2.0.php`: «Regalos» y `kilos-gratis`; `upgrade-1.4.0.php`: la columna `pct`, el hook
  `actionKpyCouponsCampana` registrado y `actionKpyProductBuyCapa` quitado, una sincronización por el
  hook nuevo y otra pendiente con imágenes). Es el camino bueno: un script propio con
  `Module::initUpgradeModule()` no actualiza (la instancia no sabe qué versión hay instalada).
  Después, el parche de `kpymenu` actualizado y vaciar caché. Todos los `upgrade` se pueden ejecutar
  más de una vez.
- Desinstalar deja la tienda como estaba para el cliente: categorías vacías y apagadas, pestaña y
  hooks fuera, caché fuera. Se quedan las tablas de datos y las imágenes (por si se vuelve a instalar).
- «Borrar todo» en la pantalla quita también categorías, raíz, imágenes, medidas y datos. Comprobado
  el 14/10: no deja filas huérfanas en `category_product`. Las campañas se quedan.
- Reinstalar sobre una instalación anterior añade las columnas que falten (`columnas()`).

## Probado en pruebas (15/10/2026, 1.1.1, sexto repaso)

Rafa: en móvil, las tarjetas de «lo más rebajado» pegadas al borde; en PC, parecía faltar un botón
debajo de cada tarjeta; el botón del menú con el nombre y el color de la campaña; y la biblioteca,
que no distinguía logo de saco ni de lata ni dejaba subir por gama. Hecho y comprobado con Cyber
Monday viva (fechas movidas al 15–19/10 para la prueba y devueltas al 30/11–6/12):

- La rejilla de «lo más rebajado» es propia (sin la clase `row` del tema, que la sacaba 12 px por
  cada lado) y el botón «Ver opciones» del tema va siempre a la vista, relleno con el acento
  secundario de la campaña. En la portada de la tienda, la misma rejilla (sin botón: ahí manda el
  tema, como en las categorías).
- El menú: «Cyber Monday» en azul noche y turquesa en PC y en el panel del móvil, en la landing, en
  la portada y en una categoría cualquiera. Sin campaña, «Ofertas y promociones» como siempre.
- La biblioteca: tres subidas de prueba por la clase (`KplBiblioteca::subir()`): el logo de Boske en
  JPG (recortado con el propio, 600×330, 2,7 s), el saco de salmón de Boske (rembg, 751×1490, 2,6 s)
  y el Eukanuba Mature con gama «Mature» (rembg, 746×1393). Tras sincronizar: el banner de Boske con
  el saco de salmón (perro) y el de gato automático apilados; el de Eukanuba 2ª unidad con el Mature
  (la promoción se llama «2a unidad al 25 % en Eukanuba Mature»); el cupón de Eukanuba con su foto
  automática (no nombra la gama). La pantalla y el paso 6 las enseñan con su tipo, marca, mascota y
  gama. Se quedan en pruebas como ejemplo.
- Tiempos (3 peticiones PC · 1 móvil): `/cybermonday` 0,40 · 0,38 · 0,40 s · móvil 0,42; `/` 0,59 ·
  0,58 · 0,57 · móvil 0,56; categoría 0,86 · 0,90 · 0,90 · móvil 0,91. Sin avisos de PHP.

## Probado en pruebas (15/10/2026, 1.1.0, quinto repaso)

La portada de la tienda con el bloque al ancho del carrusel y debajo 8 productos (4 Boske, Royal
Canin y 3 Eukanuba), en PC y en móvil (accesos y logos en filas que se deslizan); una sola banda
arriba; el Black Friday con «Stock limitado · quedan 4 días» y la cinta «ofertas por tiempo
limitado»; `/cybermonday` (programada, azul noche, turquesa y magenta) y `/reyes` (programada,
marfil, púrpura y oro) con sus textos y su paleta en PC y en móvil; el panel con las tres campañas,
los ajustes de la portada (marcas primero, cuántos productos) y la paleta (presets y tres colores).
Tiempos: portada 0,58–0,59 s (móvil 0,56 s), `/blackfriday` 0,38–0,41 s, `/cybermonday`
0,41–0,42 s, `/reyes` 0,39–0,41 s. Todas las páginas 200 sin avisos de PHP.

## Probado en pruebas (14/10/2026, 1.1.0, cuarto repaso)

La portada de la tienda sin el carrusel grande (PC y móvil), con el bloque de campaña como
portada y los logos a dos columnas; en el móvil la cinta ya no tapa el bloque ni las migas de la
ficha; las etiquetas de «lo más rebajado» y de los listados con el texto en rojo o amarillo
(comprobado con el color calculado por el navegador); `/blackfriday` y las páginas por tipo con los
matices. Columna `sin_carrusel` añadida a `kpy_promo_campana` por `tablas()` (en pruebas, a mano
con el mismo método). Tiempos: portada 0,54–0,66 s (móvil 0,60 s), `/blackfriday` 0,48–0,52 s,
`/promociones/special-price` 0,57–0,64 s. Todas las páginas 200 sin avisos de PHP.

## Probado en pruebas (14/10/2026, 1.1.0, tercer repaso)

Con la campaña del 14 al 18 de octubre, como la dejó Rafa en pruebas: `/promociones/special-price`
con la cabecera en negro, «★ Black Friday», «Black Friday: el precio ya rebajado en la ficha», el
lema con los días que quedan y la tarjeta de Boske con la etiqueta de campaña; `/promociones/cupones`
con «Cupón Black» en rojo y, en el listado, un Special Price de catálogo como «★ Black Friday
−50 %»; la página de la promoción de Boske con el título «Black Friday: −10 % en toda la gama
Boske» (también en `/blackfriday` y en `/marcas/boske`); las etiquetas de las tarjetas y de la ficha
con el texto en amarillo o rojo; la ficha de Boske y la de Eukanuba (cupón) con sus colores, y en
móvil; la cesta con «Black Friday en 1 producto» en negro y amarillo y el vale «Cupón Black −10 % ·
EUKA10» con sus colores. Tiempos: por tipo 0,43–0,65 s, promoción 0,54–0,59 s, `/blackfriday`
0,47 s, portada 0,52–0,56 s, ficha 0,49–0,52 s. Todas las páginas 200 sin avisos de PHP.

## Probado en pruebas (14/10/2026, 1.1.0)

Con la campaña adelantada a octubre: la portada de la tienda con el bloque Black Friday (título,
lema «quedan 7 días», accesos por tipo, logos de Royal Canin, Eukanuba, Alpha Spirit y Boske), la
ficha de Boske con «Black Friday −10 %» en negro y amarillo en etiqueta, franjas y precio, la de
Eukanuba con «2ª ud. Black −25 %» en negro y rojo, las tarjetas de los listados con el precio en
negro y amarillo, el bloque de Boske en su landing en negro con «Black Friday en Boske». Con el
filtro de gatos, la tarjeta de Special Price y el banner de Boske enseñan el saco de gato, y kilos
gratis el Jirafa de gato. En «lo más rebajado», los tres Eukanuba Mature con el saco solo (la
portada con la flecha «Nuevo» se queda en la ficha). Sincronización completa con imágenes: 28 s
(46 productos medidos la primera vez). Todas las páginas 200 sin avisos de PHP en modo desarrollo.

## Probado en pruebas (14/10/2026, 1.0.0)

Con las 7 promociones que hay: 8 claves, 46 productos colocados; 13 imágenes (9 sacos con rembg,
4 logos con el propio), las fotos de transición descartadas solas (Eukanuba Mature: portada con
flecha «Nuevo» fuera, saco solo dentro), el saco Jirafa de Advance en kilos gratis; todas las
direcciones contestan 200 sin avisos de PHP en modo desarrollo (`/promociones` 0,41 s, `/blackfriday`
0,36 s, listados 0,5–0,6 s); ordenar y paginar por ajax funciona; `/promociones/no-existe` manda a
«no encontrado»; el alta en el boletín sin captcha da el error controlado de `eicaptcha`. Campaña:
con las fechas adelantadas un día, `/promociones` → 302 a `/blackfriday`, cinta arriba, «Black
−10 %» en los 13 Boske y «kg gratis · Black» en los 21 de kilos gratis; con las fechas de noviembre,
«empieza en 18 días» y nada en el resto de la tienda.

## Versiones

### 1.2.0 (16/10/2026): el regalo con la compra, por lo que regala

Una promoción de regalo de `kpycoupons` entra como **«Kilos gratis»** si regala el saco pequeño de la
misma ficha (`boske-kilos-gratis`, «Boske · Kilos gratis», banner «Boske: kilos GRATIS con tu saco»
con los tamaños: «15 kg + 3 kg, 12 kg + 3 kg y 10 kg + 2,5 kg: el saco pequeño, gratis en tu cesta»)
o como **«Regalos»** si regala otra cosa (`boske-regalos`, «Boske · Regalos», con la foto del regalo
al lado del saco). La foto del banner es la del saco de siempre (no la del Jirafa). La actualización
renombra «Regalo» a «Regalos» en las categorías ya creadas.

### 1.2.1 (19/10/2026): las agotadas, fuera

Una promoción de `kpycoupons` con el **cupo** agotado (2.3.0: se vendieron todas sus unidades) cuenta
como terminada en la landing aunque le quede fecha: su categoría y su banner se van en el siguiente
reparto, como cuando acaba. Sin cambios en tablas ni hooks.

### 1.3.0 (19/10/2026): el cajón «Outlet y liquidación»

Rafa (19/10): las liquidaciones y los outlets son bajadas puntuales de combinaciones concretas (lotes
que caducan, fin de serie), de todas las marcas; no son promociones con banner. Así que un Special
Price de `kpycoupons` con etiqueta **Liquidación** u **Outlet** (`kpy_coupon.etiqueta`) no tiene
clave de marca, ni categoría propia, ni banner, ni bloque en la landing de marca: va al **cajón**, que
es la categoría permanente `outlet`, rebautizada **«Outlet y liquidación»** (`KplClaves::NOMBRE_TIPO`;
`KplCategorias::asegurar` renombra si cambia). En `KplSincronizar::promociones()` esas promociones
salen con `tipo = 'outlet'` y `cajon = true`; en `ejecutar()` se apuntan en `kpy_promo_promo` con la
clave `outlet` y sus productos se juntan con los formatos OUTLET del catálogo (las promociones
delante, más rebaja primero), con `vivas` = las suyas (para las etiquetas de campaña y el resumen).
La página `/promociones/outlet` (tipo) tiene el filtro perros/gatos y, nuevo, un botón **«Filtrar
por marca, precio o mascota»** a la categoría de PrestaShop (`filtros_url`, `tipo.php::urlFiltros`),
que es donde están los filtros de la tienda (`ps_facetedsearch`: marca, precio, la característica
«Mascota»…). Una promoción con artículos agotados (cupo por artículo, `kpycoupons` 2.4.0) sigue en
el cajón con los que le quedan; agotada del todo (`cupo_agotado`), fuera, como con fecha. Textos:
«Outlet y liquidación: últimas unidades» (`KplTextos`, `tipo.php`, `tipos.tpl`). Sin cambios en
tablas ni hooks. Va con `kpycoupons` ≥ 2.3.0 (la etiqueta) y 2.4.0 (las unidades por artículo).

### 1.3.1 (20/10/2026): el paso 6, más claro

Solo plantilla (`paso6.tpl`): la biblioteca de imágenes como tarjetas (la hoja del back office,
`.bootstrap label{display:inline-block}`, pisaba el `display:flex` de la tarjeta y el botón de radio
salía encima de la foto: lo propio del paso va bajo `.kpc-admin`), el id de producto en línea, y
elegir una foto o escribir un id marca su opción sola (un `<script>` pequeño en la plantilla; sin
él, se marca a mano). «Orden» más corto. Nada de base de datos ni de hooks.

### 1.4.0 (23/10/2026): sin llamadas a otros módulos

Julián (correo sobre el paquete del 19/10): ningún módulo llama a funciones de otro. Con
`kpycoupons` 3.0.0 (que pinta la ficha por los hooks del tema y publica sus contratos en `KpcContrato`):

- Las promociones se **preguntan** por el hook de consulta `actionKpyCouponsPromociones`
  (`KplPromociones`: una llamada por sincronización, caché estática por petición) en vez de leer
  `kpy_coupon`, `kpy_coupon_scope`, `kpy_coupon_item` y `kpy_coupon_regalo`: el estado (viva,
  programada, terminada, agotada, apagada), el cajón, la clase del regalo, las parejas, los artículos y
  los ámbitos vienen en la fila pública. `KplSincronizar` y la pantalla la consumen; si nadie contesta,
  no se toca nada (y la pantalla avisa). `grep kpy_coupon` en el PHP del módulo = 0 fuera de comentarios.
- `hookActionKpyProductBuyCapa` → `hookActionKpyCouponsCampana` (mismo cuerpo; respeta `tipos`).
- La página de una promoción de cupón lee la cookie `kpy_cupon` (`KplPromos::cuponPuesto()`) en vez
  de `Module::getInstanceByName('kpycoupons')->elegido()`.
- El **boletín** sale de la landing (su widget no pinta en nuestras páginas; pregunta 15 para Julián).
- El paso «En la landing» toma su número del parámetro `paso` del hook.
- Las etiquetas de kpycoupons se reconocen por su campo `kpc` (respaldo: clave, `type`, texto).
- `hojasDeEstilo()` ya no registra las hojas de `kpyspecialprices` ni `kpyproductflags`: las reglas
  que las tarjetas necesitan están copiadas en `promos.css`, con su origen.
- `KplBuscador` ordena por `kpy_promo_categoria.pct` (columna nueva, escrita al sincronizar) en vez
  de cruzar `kpy_coupon` en cada visita.
- `upgrade-1.4.0.php`: la columna, el hook nuevo registrado, el viejo quitado, una sincronización por
  el hook y otra pendiente con imágenes. Idempotente.
- La salida de la landing es la misma (mismas tarjetas, textos y orden), salvo la tarjeta del boletín.
