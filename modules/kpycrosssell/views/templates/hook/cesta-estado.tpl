{**
 * Kompy · La cesta de un solo almacén: la etiqueta de la línea que rompe el envío único, con su (i).
 *
 * Sin nombres de almacén y sin fechas: al cliente no se le dice de dónde sale nada, y la fecha la pone
 * el módulo de disponibilidad. Es un <details>: la etiqueta y la (i) son su resumen, y la explicación se
 * abre debajo, sin JavaScript. Va en `displayCartExtraProductInfo`, debajo del nombre y el formato.
 *}
<div class="kcs-al-estado" data-kcs-al-estado="{$kcsAl.clave|escape:'html':'UTF-8'}">
  <details class="kcs-al-info" data-kcs-al-info="{$kcsAl.clave|escape:'html':'UTF-8'}">
    <summary class="kcs-al-info__resumen">
      <span class="kcs-al-pill kcs-al-pill--mal"><i aria-hidden="true">!</i>{l s='Sale de otro almacén y puede retrasar tu pedido' d='Modules.Kpycrosssell.Shop'}</span>
      <span class="kcs-al-info__boton"><span class="kcs-al-info__i" aria-hidden="true">i</span><span class="visually-hidden">{l s='Qué significa esto' d='Modules.Kpycrosssell.Shop'}</span></span>
    </summary>
    <div class="kcs-al-info__panel">
      <p><strong>{l s='Tu pedido tiene productos que se preparan en almacenes distintos.' d='Modules.Kpycrosssell.Shop'}</strong> {l s='Para enviártelo todo junto tenemos que reunirlos primero, y eso tarda más: la fecha de entrega la ves al pie de la cesta.' d='Modules.Kpycrosssell.Shop'}</p>
      <p>{l s='Si no te importa esperar, puedes pedirlo tal cual. Si cambias este producto por uno de los elegibles, el pedido sale completo y se entrega en el plazo habitual.' d='Modules.Kpycrosssell.Shop'}</p>
    </div>
  </details>
</div>
