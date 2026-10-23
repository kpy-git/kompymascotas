{**
 * Kompy · El chip de una tarjeta de formato (hook `displayProductAttributeFlags`): «Cupón −10 %»,
 * «2ª ud. −30 %», «+ 3 kg gratis», «Liquidación»… Una sola franja por tarjeta: si el tema ya pinta
 * su tag «Precio especial» en la misma, `ficha.css` lo esconde (`.attribute-flags:has(.kpc-attr)`).
 * El color va en línea: es el único que gana a la hoja del tema.
 *}
<div class="kpc-attr kpc-attr--{$chip.type}" style="background-color:{$chip.background};color:{$chip.color}">{$chip.label}</div>
