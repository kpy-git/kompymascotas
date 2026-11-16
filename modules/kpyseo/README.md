# kpyseo · SEO técnico de la tienda

Lo que PrestaShop 9 y el tema no hacen solos y Google necesita para entender kompymascotas.com. Todo desde un
módulo: ni core, ni tema, ni tablas nuevas. Plan y motivos: `docs/plan-seo-2026-11.md` (frente 2).

| Qué hace | Cómo | Dónde se ve |
|---|---|---|
| **Sitemaps al día** | Los genera el módulo (`/sitemap.xml` índice + páginas, categorías, marcas y productos de 5.000 en 5.000, con imagen de portada) y los regenera solo cada 24 h cuando alguien los pide. Fuera: categorías vacías, productos apagados o sin visibilidad, filtros, paginaciones | `/sitemap.xml` |
| **Marcas** | Canónica en `/marcas` y `/marcas/<marca>`; título y descripción tomados del meta título y la meta descripción de la marca (Catálogo › Marcas) y, si faltan, generados con sentido | cabecera de `/marcas/royal-canin` |
| **Categorías vacías** | `noindex, follow` automático cuando no hay ningún producto visible ni en la categoría ni en sus hijas; tampoco entran en el sitemap | `<meta name="robots">` |
| **JSON-LD de producto** | Completa el del tema: `gtin13` de la combinación (el EAN está en las combinaciones, no en el producto), envío (`shippingDetails` con portes o gratis según el umbral de Transporte), devoluciones (`hasMerchantReturnPolicy`), marca y descripción. Quita el `mpn` inventado (el tema ponía la referencia o el id) y pone `priceValidUntil` solo cuando la rebaja tiene fecha de fin (el tema ponía siempre «hoy + 15 días») | fuente de cualquier ficha |
| **Centro de soporte** | En `/soporte/…` cada artículo lleva su pregunta como título y el principio de la respuesta como descripción; el índice, un título y una descripción con sentido (el módulo de soporte ponía el mismo título en los 27) | cabecera de `/soporte/cancelar-mi-pedido` |
| **Marcas sin productos** | `noindex, follow` en la landing de una marca que no tiene ningún producto visible | `<meta name="robots">` |
| **Open Graph / Twitter** | `og:image` (imagen de categoría, de marca o la configurada), `og:locale`, `twitter:card` | cabecera |
| **Organización** | JSON-LD `Organization` en la portada: razón social, NIF, dirección, teléfono, perfiles (`sameAs`) | portada |
| **Alt de imágenes** | Cuando una imagen no tiene leyenda: «Producto – Marca» | fichas y listados |
| **H1 de portada** | Un H1 de texto configurable (el tema usaba el logo); va con un parche mínimo del tema que quita el `<h1>` del logo | portada |
| **IndexNow** | Avisa a Bing y compañía al guardar una ficha, categoría, marca o página; la clave se publica en `/<clave>.txt` (la sirve el módulo: en `modules/` Apache no deja leer `.txt`); botón para enviar todo el sitemap | pantalla del módulo |
| **Redirecciones** (1.1.0) | Búsquedas cuyo término es exactamente una marca → `/marcas/<marca>` (301; son las URL del dominio viejo que aún traen clics); `index.php?controller=product&id_product=N` (y category, cms, manufacturer) → URL amigable (301) si el objeto está activo; limpieza de la query duplicada que deja la redirección del dominio viejo (`?a=b?a=b&src=pym`); lista propia «/vieja \| /nueva \| 301», con prefijos (`/blog/*`) y 410. Solo GET del front; nunca controladores de módulos ni el back office | `actionDispatcherBefore`, antes de resolver el controlador |

## Configuración

Módulos › kpyseo › Configurar: H1 y subtítulo de la portada, imagen para compartir, logo, razón social, NIF,
perfiles, interruptores de cada función, datos de envío y devolución para el JSON-LD, horas entre
regeneraciones del sitemap. Dos botones: regenerar sitemaps y enviar todas las URL a IndexNow.

## Lo que hay que hacer fuera del módulo

- `robots.txt`: línea `Sitemap: https://kompymascotas.com/sitemap.xml`.
- Search Console y Bing Webmaster: enviar `https://kompymascotas.com/sitemap.xml` y quitar el de gsitemap cuando este
  lleve unos días sin errores. El módulo gsitemap se puede desactivar entonces.
- Parche del tema (`parche-kpy_theme-h1-portada.patch`): el logo deja de ir dentro de `<h1>` en la portada.
- Servidor del dominio viejo (piensoymascotas.com): su regla de redirección duplica la query string (`?a=b?a=b&src=pym`). Con la bandera `QSA` y sin repetir `%{QUERY_STRING}` queda bien; mientras, el módulo la limpia.

## Reglas que cumple

Sin llamadas a otros módulos (de kpymanufacturer solo se leen dos tablas, y si no existen no pasa nada); sin filas en
tablas ajenas (no tiene tablas); estilos por `actionFrontControllerSetMedia`; posición del hook de la portada por la
API (`updatePosition` al instalar); nada en el tema salvo el parche del H1, que va aparte y es opcional.

## Hooks

`displayHeader`, `displayContentWrapperTop` (el H1, solo en la portada y en primera posición), `actionFrontControllerSetMedia`, `actionPresentProduct`, `actionPresentProductListing`,
`actionOutputHTMLBefore`, `moduleRoutes`, `actionDispatcherBefore` (redirecciones, desde la 1.1.0) y los `actionObject*After` de producto, categoría, marca y CMS (IndexNow).

## Versiones

- 1.0.0 (9/11/2026): todo lo de la tabla salvo las redirecciones.
- 1.1.0 (9/11/2026): redirecciones (`upgrade/upgrade-1.1.0.php` registra el hook y crea los cuatro ajustes sin tocar los demás).
