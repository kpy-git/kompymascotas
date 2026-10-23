{**
 * Kompy · La promoción de cada fila de la cesta, debajo de su nombre (hook
 * `displayCartExtraProductActions`, ver `KpcLineas::bloques`): el regalo («Regalo · Saco de 3 Kg ·
 * 0,00 € · Con tu Saco de 12 Kg»), la versión con regalo, el Special Price, la 2ª unidad o el cupón.
 * Un bloque por promoción, con su chip y los colores de la ficha.
 *}
{* En su propia fila: el tema pone las acciones de la línea en fila (al lado del «Recíbelo entre…»). *}
<div class="kpc-lr-fila">
  {foreach $kpc_bloques as $b}
    <div class="kpc-lr kpc-lr--{$b.tipo|escape:'html':'UTF-8'}">
      <span class="kpc-lr__chip">{if $b.icono === 'regalo'}<i class="material-icons" aria-hidden="true">redeem</i>{elseif $b.icono === 'especial'}<span class="kpc-lr__estrella" aria-hidden="true">★</span>{/if}{$b.chip|escape:'html':'UTF-8'}</span>
      <span class="kpc-lr__texto">
        <span>{if $b.fuerte}<strong>{$b.fuerte|escape:'html':'UTF-8'}</strong>{if $b.texto || $b.precio} · {/if}{/if}{$b.texto|escape:'html':'UTF-8'}{if $b.precio}<strong>{$b.precio|escape:'html':'UTF-8'}</strong>{/if}{if $b.tachado} <s>{$b.tachado|escape:'html':'UTF-8'}</s>{/if}</span>
        {if $b.sub}<span class="kpc-lr__sub">{$b.sub|escape:'html':'UTF-8'}</span>{/if}
      </span>
    </div>
  {/foreach}
</div>
