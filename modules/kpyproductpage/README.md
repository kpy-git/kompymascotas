# kpyproductpage · Kompy · Ficha de producto

Módulo nuevo e independiente. Pinta la parte de abajo de la ficha: características, beneficios,
ingredientes, composición analítica, ración diaria (con calculadora) y descripción. Es una sola
plantilla para todos los productos, pensada primero para móvil.

- **No depende de ningún otro módulo**: no llama a sus clases ni a sus plantillas. Solo **lee** las
  tablas que ya se rellenan al dar de alta un producto (ver abajo). Si una tabla no existe, esa
  parte no sale.
- **No escribe nada**: no crea tablas ni cambia datos. Guarda cuatro valores de configuración.
- **No toca el core.** En el tema hacen falta tres líneas alrededor de la llamada de siempre (abajo).
- **Nunca rompe la ficha.** Si falla algo, devuelve vacío, lo apunta en el registro de
  PrestaShop y el tema pinta la ficha de siempre.

## Desde la 1.1.0 (30/09/2026)

- **Los cuatro bloques alineados en PC.** Características + Beneficios y Ingredientes +
  Composición usan la misma rejilla (3/5 y 2/5): el corte entre bloques cae en el mismo sitio arriba
  y abajo, y los dos de cada fila miden lo mismo de alto. Si una fila tiene un solo bloque, ocupa
  todo el ancho.
- **Boske, con la ficha nueva** (antes excluida: sus fichas eran plantillas hechas a mano en
  `kpyproductdescription`). Lo que esas plantillas tenían y no está en las tablas del catálogo va
  ahora en `marcas/178-boske.json` (`classes/KppMarca.php`): opinión de la especialista, fotos con
  su texto, beneficios ilustrados (en el bloque de beneficios, en vez de los de la tabla), beneficios
  explicados («Por qué elegirlo», en tarjetas del color de cada producto, como en la ficha de
  siempre), ingredientes con foto y preguntas frecuentes (bloque propio, cada una desplegable).
  Solo se lee el fichero de la marca del producto; las demás fichas no leen nada más.
  - **Imágenes**: las fotos de las plantillas eran PNG de 300 KB a 4 MB (hasta 12 MB por ficha); van
    en `views/img/marcas/boske/` en WebP y al tamaño al que se ven: de 49 MB a 2,6 MB en total, unos
    200-360 KB por ficha. Todas con `loading="lazy"`, ancho y alto (no mueven la página). Los 9
    iconos del gato que salían rotos (`beneficios/` en vez de `benefits/`) ya se ven.
  - **Datos al catálogo, como el resto**: composición, ración (girada a «peso del perro» en filas,
    que es lo que pregunta la calculadora), aditivos al final de los ingredientes y descripción sin
    los 65 KB de estilos de Word. Va en SQL aparte (`sql/boske-*.sql` del paquete), con copia previa
    y vuelta atrás.
  - Una ficha con contenido de marca sale entera aunque le falte la analítica (hoy, la Clinical Diet
    Hypoallergenic en pienso, #9026: no la tiene en ningún sitio; hace falta su etiqueta).
  - El upgrade a la 1.1.0 quita Boske (178) de «Marcas con su ficha de siempre».
- **Beneficios con textos más cortos** (Rafa): los que pasaban de 3 líneas en móvil o en PC, medido
  en el navegador. Es un cambio de datos (`sql/beneficios-cortos.sql`), no del módulo.
- Listas de cosas cortas en la descripción (`ul.kpp-lista-corta`): en columnas.

## Qué pesa

| | Sin comprimir | gzip |
|---|---|---|
| `views/css/ficha.css` | 7,9 KB | 2,4 KB |
| `views/js/ficha.js` (diferido, al final) | 4,4 KB | 1,5 KB |
| HTML de la sección (Lily's #10661) | ~9 KB | — |

Solo se cargan en la ficha de producto. Sin librerías, sin fuentes nuevas. Los desplegables son
`<details>` nativos: funcionan sin JavaScript. Los iconos de beneficios cargan en diferido y con
tamaño fijo (no mueven la página). Las imágenes de la descripción pasan a carga diferida y reciben
`alt` si no lo tienen: son las que hoy hacen pesar 4,4 MB la ficha de Royal Canin.

Consultas por ficha: 5 pequeñas (beneficios, ingredientes, composición, ración, formatos) más 2 de
metadatos que se hacen una vez por petición. Las características salen de
`Product::getFrontFeaturesStatic`, que el tema ya consulta y queda en caché.

## Qué lee

| Tabla | Para qué |
|---|---|
| `feature_product` y compañía | Características: «Para» (mascota, edad, raza, tamaño) y fichas |
| `kpy_benefit_product`, `kpy_benefits`, `kpy_benefit_lang` | Beneficios y su icono |
| `kpy_product_ingredients` | Texto de ingredientes. La barra sale solo si el **primer** ingrediente dice su % («Maíz entero, trucha (17 %)…» no la lleva: la trucha no es lo principal). Si el primero es una categoría de la etiqueta («Carnes y subproductos animales (pollo 43 %)»), vale lo de dentro; si detrás viene la lista detallada («Ingredientes: con pollo: hígado de cerdo, pollo (9 %)…»), manda la detallada |
| `kpy_product_component`, `kpy_component_lang` | Composición analítica |
| `kpy_product_rations_table` | Ración diaria (HTML con tablas). La calculadora entiende también las tablas oficiales: título encima de cada tabla (pestaña), varias filas de cabecera (modo y unidad: «3-4» + «Bolsitas al día»), celdas unidas, la mixta con «+» y fracciones («1¾»). Una tabla de composición metida en la ración se salta |
| `product_attribute` y compañía, `kpy_product_attribute` | Formatos a la venta (sin los apagados) |

El icono está en la columna `image` de `kpy_benefits`, sin extensión (`digestivo`): el módulo
añade `.svg`, igual que `kpyproductdescription`. Si un beneficio tiene `protege = 1`
(antiparasitarios), la sección se titula «Protege contra», como hasta ahora.

## Instalar (en pruebas)

1. Copiar la carpeta a `modules/kpyproductpage/`.
2. Instalarlo desde Módulos o con `php bin/console prestashop:module install kpyproductpage`.
   Registra tres hooks: `displayKpyProductPage` (lo crea), `actionFrontControllerSetMedia` y
   `displayFooterProduct` (este solo para la previsualización).
3. Configurarlo:
   - **Marcas con su ficha de siempre**: vacío. (Hasta la 1.0.0, `178`, Boske; desde la 1.1.0
     Boske sale con la ficha nueva y su contenido propio.)
   - **Plantilla nueva en todos los productos**: apagado. Así solo cambian las fichas **completas**
     (con ingredientes y composición cargados), que son las nuevas: 521 en pruebas el 22/09
     (Farmina, Schesir, Lily's, Eukanuba, IAMS…). Las demás, Royal Canin incluido, se quedan
     como están hasta que se repase su marca. Tener beneficios no sirve de criterio: los tiene
     casi todo el catálogo, hasta los juguetes.
   - **Carpeta de iconos**: vacío. Usa los SVG de siempre (`kpyproductdescription/views/img/benefits/`).
     Si algún día se quiere que este módulo no dependa ni de esa carpeta, se copian los iconos a
     `kpyproductpage/views/img/beneficios/` y se pone esa ruta aquí.
4. **Cambio en el tema** (`themes/kpy_theme/templates/catalog/product.tpl`): envolver la llamada a
   `kpyproductdescription` del bloque `product_description`. Es el parche
   `entregas/kpyproductpage/PARCHE-tema-product-tpl.patch`, comprobado con `git apply --check`
   contra el tema de pruebas del 22/09:

   ```smarty
   {capture name='kpp'}{hook h='displayKpyProductPage' product=$product}{/capture}
   {if $smarty.capture.kpp|trim}{$smarty.capture.kpp nofilter}{else}
   {widget name='kpyproductdescription' hook='description' id_product=$product.id}
   {/if}
   ```

   Las opiniones (`productcomments`) y Skeepers van fuera de ese bloque y no cambian.

   **Probar sin tocar el tema:** con `KPP_PREVIA = 1`, añadir `?kpp=1` a la URL de una ficha. La
   sección nueva se pinta al pie y un script de una línea la sube a su sitio, ocultando la vieja.
   Se recuerda mientras se navega (cookie de la tienda); `?kpp=0` la apaga. Sin haberlo puesto,
   la ficha es la de siempre. En producción, `KPP_PREVIA = 0`.

5. Vaciar la caché de PrestaShop (Parámetros avanzados → Rendimiento), que regenera los ficheros
   combinados de CSS y JS del tema.

## Volver atrás

- **Al momento:** desactivar el módulo. El hook no devuelve nada y el tema pinta la ficha de
  siempre (por eso el `{else}`).
- **Del todo:** desinstalar el módulo y deshacer el parche del tema (`git apply -R`).

## Pruebas sin tienda

```bash
php tests/lectores.php [paquetes-de-altas/*.sql]   # lectores de ingredientes, composición, ración, formatos
php tests/pintar.php <carpeta> [Smarty.class.php]  # pinta la plantilla con datos reales (Lily's, Schesir)
# En el servidor, desde la raíz de la tienda: todas las fichas completas, contando errores y avisos
docker exec -i -w /var/www/html/kompy php85 php < tests/todas.php
```

La carpeta `tests/` **no se sube** a la tienda: se podría ejecutar desde la web.

Resultado en pruebas el 22/09: 521 fichas completas, 521 pintadas, 0 excepciones y 0 avisos de PHP.
83 con calculadora de ración y 457 con barra de ingrediente principal. El bloque ocupa de 5,9 a
14,6 KB de HTML (mediana 9,4 KB), frente a unos 8 KB del de siempre.

`lectores.php` pasa, además de los casos fijos, todos los ingredientes y raciones de los paquetes
de altas que se le den: con los de septiembre de 2026, 1.701 textos de ingredientes y 132 raciones,
ninguno rompe.

## Cómo encaja con los demás módulos nuevos

Cada pieza es un módulo aparte que se puede encender y apagar sola, y se hablan por hooks:

| Módulo | Qué hace | Estado |
|---|---|---|
| `kpyproductpage` | Parte de abajo de la ficha (este) | Instalado y probado en pruebas el 22/09, en previsualización |
| flags (nuevo) | Flags con nombre corto: foto, listado y franja de la tarjeta de formato | Por hacer |
| cupones (nuevo) | Cupón en la ficha, precio por combinación, activación por enlace | Por hacer |
| arriba de la ficha | Tarjetas de formato, precio, cantidad − / +, botón | Por hacer (es tema) |

Para enlazarlos, este módulo lanza su contenido por `displayKpyProductPage` y no guarda estado.
Los otros podrán colgarse de sus propios hooks (`displayKpyFlags`, `actionKpyPrecioFicha`…) sin
tocar este.
