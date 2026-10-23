{**
 * Kompy · Las promociones de la cesta, dentro de los totales (se vuelve a pintar en cada cambio).
 *
 *   - `data-kpc-datos`: qué es cada vale de PrestaShop (cupón o 2ª unidad), para que `cesta.js`
 *     dibuje su lista con el color y el icono de cada uno. Los importes los pone PrestaShop.
 *   - lo que no es un vale pero se ahorra (Special Price) y el empujón «añade 1 ud.», que
 *     `cesta.js` coloca junto a la lista de vales, encima de «¿Tienes un cupón?». El empujón es un
 *     botón: suma esa unidad sin tener que buscar el «+» de la línea.
 *}
<script type="application/json" data-kpc-datos>{$kpc.datos nofilter}</script>
{if $kpc.lineas || $kpc.animos}
  <div class="kpc-extra" data-kpc-extra>
    {foreach $kpc.lineas as $l}
      <p class="kpc-linea kpc-linea--{$l.clase}"{if isset($l.estilo)} style="{$l.estilo}"{/if}>
        <span class="kpc-linea__icono" aria-hidden="true"{if isset($l.estilo_icono)} style="{$l.estilo_icono}"{/if}>{if $l.clase === 'especial'}★{elseif $l.clase === 'segunda'}2ª{elseif $l.clase === 'regalo'}<i class="material-icons">redeem</i>{elseif $l.clase === 'tope'}<i class="material-icons">info</i>{else}%{/if}</span>
        <span class="kpc-linea__texto">{$l.texto}</span>
        <strong class="kpc-linea__importe">{$l.importe}</strong>
      </p>
    {/foreach}
    {foreach $kpc.animos as $a}
      <button type="button" class="kpc-animo" data-kpc-sube="{$a.sube|escape:'html':'UTF-8'}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
        <span>{$a.texto}</span>
      </button>
    {/foreach}
  </div>
{/if}
