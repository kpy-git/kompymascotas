# kpyproductzoom · Ampliar las fotos con los dedos

**El problema:** en móvil no se podían ampliar las fotos. El tema abre una galería grande, pero solo
desde un botón de lupa pequeño, y dentro no se puede hacer zoom. Quien compra pienso mira el saco,
la etiqueta y la tabla de raciones: si no puede acercarse, se va.

**Lo que hace:**

- Tocar la foto de la ficha abre la galería grande (antes había que acertar con la lupa).
- Dentro: pellizcar con dos dedos amplía hasta 4 veces (se puede cambiar), dos toques amplían y
  vuelven, y con la foto ampliada se arrastra para moverse.
- Mientras está ampliada no se pasa de foto sin querer (las flechas se apagan).
- La primera vez aparece una pista: «Pellizca para ampliar».

**Lo que NO hace:** no toca el tema ni la galería. Se cuelga de lo que ya hay (`#product-modal`,
`.product__carousel`, `.product__zoom`). Si se desactiva el módulo, la ficha se queda como estaba.

## Instalar

1. Copiar la carpeta a `modules/kpyproductzoom`.
2. Instalarlo (back office o `php bin/console prestashop:module install kpyproductzoom`).
3. Vaciar la caché.

Configuración: cuánto se puede ampliar como mucho (de 2 a 8 veces; por defecto 4).

## Detalles

- Sin librerías: 4 KB de JavaScript y 1 KB de CSS, cargados solo en la ficha y diferidos.
- Usa eventos de puntero (`pointerdown/move/up`), que es lo que entienden a la vez el móvil y el
  ratón; en PC no cambia nada porque la galería del tema ya se abre con la lupa.
- La foto grande que enseña la galería es la que ya servía el tema (`product_main_2x`, 1.440 px).
  Si se quiere más nitidez al ampliar del todo, eso va con el tamaño de imagen, no con este módulo.
