{**
 * Kompy · La cesta de un solo almacén en la ventana «Añadido a tu cesta» (`displayCartModalFooter`):
 * si lo que acaba de entrar rompe el envío único, su etiqueta con la (i) y el bloque «Cámbialo por…»
 * (si hay alternativas; si no, la etiqueta sola). La hoja de estilos lo coloca encima de los botones.
 *}
<div class="kcs-al-ventana" data-kcs-al-ventana="{$kcsAl.linea.clave|escape:'html':'UTF-8'}">
  {include file='module:kpycrosssell/views/templates/hook/cesta-estado.tpl' kcsAl=['clave' => $kcsAl.linea.clave]}
  {if $kcsAl.tarjetas}
    {include file='module:kpycrosssell/views/templates/hook/cesta-cambialo.tpl' kcsAl=$kcsAl}
  {/if}
  <p class="kcs-al-ventana__hecho" data-kcs-al-hecho hidden><i aria-hidden="true">&#10003;</i> {l s='Cambiado: tu pedido sale completo y sin retraso' d='Modules.Kpycrosssell.Shop'}</p>
</div>
