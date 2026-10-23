{**
 * Kompy · La ventana «Añadido a tu carrito» (hook `displayCartModalFooter` del tema): la promoción
 * de la línea que acaba de entrar, como la pinta la cesta debajo del nombre (`KpcLineas::bloques`).
 * Sin esto, la ventana enseña el precio de la línea sin decir que lleva cupón, 2ª unidad o regalo
 * (Rafa, 30/09: «parecía que el producto había entrado a otro precio»).
 *}
<div class="kpc-modal">
  <p class="kpc-modal__titulo">{$kpc_titulo}</p>
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
</div>
