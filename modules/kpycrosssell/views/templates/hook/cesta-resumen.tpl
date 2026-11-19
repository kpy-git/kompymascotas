{**
 * Kompy · La cesta de un solo almacén: el aviso al pie de la lista de la cesta (`displayShoppingCartFooter`).
 *
 * Ámbar mientras la cesta mezcla almacenes, con cuántos productos hay que cambiar y un enlace al primero.
 * Verde solo justo después de un cambio (sin JavaScript se vuelve aquí con `kcs=cambiado`) cuando ya no
 * mezcla. Sin fechas: la pone el módulo de disponibilidad.
 *}
<div class="kcs-al-resumen kcs-al-resumen--{$kcsAlResumen.color|escape:'html':'UTF-8'}" data-kcs-al-resumen role="status">
  <div class="kcs-al-resumen__icono" aria-hidden="true">{if $kcsAlResumen.color == 'verde'}&#10003;{else}!{/if}</div>
  <div class="kcs-al-resumen__texto">
    {if $kcsAlResumen.color == 'verde'}
      <h3 class="kcs-al-resumen__titulo">{l s='Cambiado: tu pedido sale completo y sin retraso' d='Modules.Kpycrosssell.Shop'}</h3>
    {elseif $kcsAlResumen.color == 'rojo'}
      <h3 class="kcs-al-resumen__titulo">{l s='No se ha podido cambiar. Inténtalo otra vez.' d='Modules.Kpycrosssell.Shop'}</h3>
    {else}
      <h3 class="kcs-al-resumen__titulo">{l s='Tu pedido sale de varios almacenes y puede retrasarse' d='Modules.Kpycrosssell.Shop'}</h3>
      <p>
        {if $kcsAlResumen.n == 1}
          {l s='Cambia el producto marcado por uno de los elegibles y saldrá sin retraso.' d='Modules.Kpycrosssell.Shop'}
        {else}
          {l s='Cambia los %n% productos marcados por productos elegibles y saldrá sin retraso.' sprintf=['%n%' => $kcsAlResumen.n] d='Modules.Kpycrosssell.Shop'}
        {/if}
        {if $kcsAlResumen.primera}<a href="#kcs-al-{$kcsAlResumen.primera|escape:'html':'UTF-8'}">{l s='Ver el producto' d='Modules.Kpycrosssell.Shop'}</a>{/if}
      </p>
    {/if}
  </div>
</div>
