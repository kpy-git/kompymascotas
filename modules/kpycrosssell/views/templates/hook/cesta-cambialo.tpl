{**
 * Kompy · La cesta de un solo almacén: el bloque «Cámbialo por…» de una línea que rompe el envío único.
 *
 * Tres alternativas a la vista y «Ver más productos elegibles» (un <details>: funciona sin JavaScript),
 * «Dejarlo como está» (un formulario) y las marcas que no retrasan el pedido, con enlace a su página.
 * Sin nombres de almacén y sin fechas. Va en `displayCartExtraProductActions` (la fila de acciones de la
 * línea, a todo el ancho) y, con `origen = ventana`, en la ventana «Añadido a tu cesta».
 *}
<section class="kcs-al{if $kcsAl.origen == 'ventana'} kcs-al--ventana{/if}" id="kcs-al-{$kcsAl.linea.clave|escape:'html':'UTF-8'}"
         data-kcs-al="{$kcsAl.linea.clave|escape:'html':'UTF-8'}" data-kcs-al-origen="{$kcsAl.origen|escape:'html':'UTF-8'}"
         data-kcs-al-url="{$kcsAl.url|escape:'html':'UTF-8'}" data-kcs-al-token="{$kcsAl.token|escape:'html':'UTF-8'}"
         aria-label="{l s='Alternativas para no retrasar tu pedido' d='Modules.Kpycrosssell.Shop'}">
  <h4 class="kcs-al__titulo">{l s='Si te corre prisa, te recomendamos cambiarlo por un producto elegible: todo tu pedido sale sin retraso' d='Modules.Kpycrosssell.Shop'}</h4>
  {if $kcsAl.parecidos}
    <p class="kcs-al__sub">{l s='Productos similares, de otras marcas de calidad y de precio parecido. Con cualquiera de ellos tu pedido sale completo y sin retraso.' d='Modules.Kpycrosssell.Shop'}</p>
  {else}
    <p class="kcs-al__sub">{l s='Productos similares, de otras marcas de calidad; el precio puede variar y lo ves en cada uno. Con cualquiera de ellos tu pedido sale completo y sin retraso.' d='Modules.Kpycrosssell.Shop'}</p>
  {/if}

  <div class="kcs-al__tarjetas">
    {foreach $kcsAl.tarjetas as $t}
      {include file='module:kpycrosssell/views/templates/hook/_cesta-tarjeta.tpl' t=$t bloque=$kcsAl}
    {/foreach}
  </div>

  <div class="kcs-al__acciones">
    {if $kcsAl.extras}
      <details class="kcs-al__mas" data-kcs-al-mas>
        <summary class="btn btn-outline-primary kcs-al__vermas"><span class="kcs-al__vermas-mas">{l s='Ver más productos elegibles' d='Modules.Kpycrosssell.Shop'}</span><span class="kcs-al__vermas-menos">{l s='Ver menos' d='Modules.Kpycrosssell.Shop'}</span></summary>
        <div class="kcs-al__tarjetas kcs-al__tarjetas--mas">
          {foreach $kcsAl.extras as $t}
            {include file='module:kpycrosssell/views/templates/hook/_cesta-tarjeta.tpl' t=$t bloque=$kcsAl}
          {/foreach}
        </div>
      </details>
    {/if}
    {if $kcsAl.origen == 'linea'}
      <form method="post" action="{$kcsAl.url|escape:'html':'UTF-8'}" class="kcs-al__dejar-form" data-kcs-al-dejar>
        <input type="hidden" name="token" value="{$kcsAl.token|escape:'html':'UTF-8'}">
        <input type="hidden" name="accion" value="dejar">
        <input type="hidden" name="de" value="{$kcsAl.linea.clave|escape:'html':'UTF-8'}">
        <button type="submit" class="kcs-al__dejar">{l s='Dejarlo como está' d='Modules.Kpycrosssell.Shop'}</button>
      </form>
    {/if}
  </div>

  {if $kcsAl.marcas}
    <p class="kcs-al__marcas">{l s='Productos de estas marcas no retrasan tu pedido:' d='Modules.Kpycrosssell.Shop'}
      {foreach $kcsAl.marcas as $m}<a href="{$m.url|escape:'html':'UTF-8'}">{$m.nombre|escape:'html':'UTF-8'}</a>{/foreach}
    </p>
  {/if}

  <p class="kcs-al__aviso" data-kcs-al-aviso role="status" aria-live="polite"></p>
  <script type="application/json" data-kcs-al-textos>{ldelim}
    "cambiado": "{l s='Cambiado: tu pedido sale completo y sin retraso' d='Modules.Kpycrosssell.Shop' js=1}",
    "sigue": "{l s='Cambiado. Aún hay otro producto que puede retrasar tu pedido: míralo más abajo.' d='Modules.Kpycrosssell.Shop' js=1}",
    "error": "{l s='No se ha podido cambiar. Inténtalo otra vez.' d='Modules.Kpycrosssell.Shop' js=1}",
    "cambiando": "{l s='Cambiando…' d='Modules.Kpycrosssell.Shop' js=1}"
  {rdelim}</script>
</section>
