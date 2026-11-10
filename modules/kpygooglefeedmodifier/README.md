# kpygooglefeedmodifier · etiquetas de campaña del feed

Módulo de Julián (29/10/2026, commit `a33af8e`) terminado por kompy-web (1.1.0, 9/11/2026) según el contrato del
apartado 5 de `C:\kompymascotas-ads\sistema-ads\14_ETIQUETAS_PERSONALIZADAS.md`.

| Qué | Cómo |
|---|---|
| **La tabla** `PREFIX_kpy_google_feed_custom_labels` (`id_product`, `id_product_attribute`, `custom_label_0`) | La crea el módulo (1.0.0). De ella lee el generador del feed de Merchant, que corre fuera de PrestaShop: su estructura no se toca |
| **Recibir la lista** | `POST /module/kpygooglefeedmodifier/etiquetas` con la clave en `X-Kompy-Clave` y un JSON `{"origen": "…", "probar": false, "etiquetas": [["236-328", "excluir"], …]}` |
| **Comprobar antes de tocar** | 403 si la clave no coincide (`hash_equals`); 400 si el JSON no vale; 422 si un id no es `producto-combinación`, una etiqueta no es `[a-z0-9_]{1,40}`, hay un id repetido o hay menos de 5.000 filas. `probar: true` hace todo menos escribir |
| **Sustituir de una vez** | Las filas se cargan en una tabla gemela y se intercambia con `RENAME TABLE` (atómico): quien lea ve la lista vieja o la nueva, nunca una a medias ni vacía. Si algo falla, la original sigue tal cual |
| **Responder** | `{"ok": true, "probado": false, "filas": 8134, "antes": 5366, "por_etiqueta": {"pmax": 5374, …}}`; si falla, `{"ok": false, "error": "…"}` con su código |
| **Back office** | Módulos › KpyGoogleFeedModifier › Configurar: última actualización (fecha, origen, filas), recuento por etiqueta, buscador por id y la clave con «Generar nueva» |

## Ajustes (Configuration)

`KGFM_CLAVE` (40 caracteres hexadecimales, se genera al instalar o actualizar), `KGFM_ULTIMA_FECHA`, `KGFM_ULTIMA_ORIGEN`,
`KGFM_ULTIMA_FILAS`. La clave no se escribe en ningún registro ni sale en las respuestas.

## Versiones

- 1.0.0 (Julián, 29/10): la tabla, cargada a mano con el fichero del 29/10.
- 1.1.0 (kompy-web, 9/11): controlador `etiquetas`, página del back office, `upgrade/upgrade-1.1.0.php` (solo crea la
  clave y los ajustes; la tabla no se toca).

## Reglas

**Nunca desinstalar ni reinicializar**: `uninstall.sql` borra la tabla y el feed se quedaría sin etiquetas hasta la
mañana siguiente. Las versiones nuevas llegan como actualización. No llama a otros módulos, no escribe en tablas ajenas,
no toca el generador del feed.
