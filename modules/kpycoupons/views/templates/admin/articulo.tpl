{**
 * Un artículo elegido con el buscador (el regalo «el mismo para todos», el de reserva): el campo
 * escondido con «producto-formato», lo elegido (nombre, marca, precio, stock) con su «×», y el
 * buscador, que pide los artículos sin recargar (`&ajax=1&action=buscar&q=`); lo maneja `admin.js`.
 *
 * Variables: `$nombre` (del campo), `$valor` («8299-92468» o vacío), `$dichos` (KpcPantalla::articulosDichos).
 *}
{assign var=dicho value=null}
{if $valor && isset($dichos[$valor])}{assign var=dicho value=$dichos[$valor]}{/if}
<div class="kpc-articulo" data-kpc-articulo>
  <input type="hidden" name="{$nombre|escape:'html':'UTF-8'}" value="{$valor|escape:'html':'UTF-8'}" data-kpc-articulo-valor>
  <div class="kpc-articulo__elegido{if $dicho && !$dicho.vendible} is-mal{/if}" data-kpc-articulo-elegido {if !$valor}hidden{/if}>
    <span class="kpc-articulo__texto">
      <strong data-kpc-articulo-texto>{if $dicho}{$dicho.texto|escape:'html':'UTF-8'}{else}{$valor|escape:'html':'UTF-8'}{/if}</strong>
      <small data-kpc-articulo-detalle>{if $dicho}{$dicho.detalle|escape:'html':'UTF-8'}{/if}</small>
    </span>
    <button type="button" class="kpc-ficha__quitar kpc-articulo__quitar" data-kpc-articulo-quitar aria-label="Quitar">×</button>
  </div>
  <input type="search" class="form-control" placeholder="Escribe el nombre, el EAN, la referencia o el id…" autocomplete="off" spellcheck="false" aria-label="Buscar un artículo" data-kpc-articulo-buscar {if $valor}hidden{/if}>
  <ul class="kpc-opciones" data-kpc-opciones hidden></ul>
</div>
