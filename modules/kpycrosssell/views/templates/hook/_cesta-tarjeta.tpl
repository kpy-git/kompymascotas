{**
 * Una alternativa: foto, nombre, formato, precio y la diferencia con la línea, y el botón «Cambiar».
 * Es un formulario: sin JavaScript cambia igual (el controlador vuelve a la cesta); con JavaScript, sin recargar.
 *}
{assign var=kcsNombre value=$t.nombre|escape:'html':'UTF-8'}
<form class="kcs-al-tarjeta" method="post" action="{$bloque.url|escape:'html':'UTF-8'}" data-kcs-al-cambiar>
  <input type="hidden" name="token" value="{$bloque.token|escape:'html':'UTF-8'}">
  <input type="hidden" name="accion" value="cambiar">
  <input type="hidden" name="origen" value="{$bloque.origen|escape:'html':'UTF-8'}">
  <input type="hidden" name="de" value="{$bloque.linea.clave|escape:'html':'UTF-8'}">
  <input type="hidden" name="a" value="{$t.id_product|intval}-{$t.id_product_attribute|intval}">
  <a class="kcs-al-tarjeta__foto" href="{$t.url|escape:'html':'UTF-8'}" tabindex="-1" aria-hidden="true">{if $t.foto}<img src="{$t.foto|escape:'html':'UTF-8'}" alt="" loading="lazy" width="56" height="56">{/if}</a>
  <div class="kcs-al-tarjeta__texto">
    <a class="kcs-al-tarjeta__nombre" href="{$t.url|escape:'html':'UTF-8'}">{$kcsNombre nofilter}</a>
    {if $t.formato}<span class="kcs-al-tarjeta__formato">{$t.formato|escape:'html':'UTF-8'}</span>{/if}
  </div>
  <div class="kcs-al-tarjeta__pie">
    <span class="kcs-al-tarjeta__precio"><b>{$t.precio_txt|escape:'html':'UTF-8'}</b>{if $t.diferencia_txt}<span class="kcs-al-tarjeta__delta">{$t.diferencia_txt|escape:'html':'UTF-8'}</span>{/if}</span>
    <button type="submit" class="btn btn-primary kcs-al-tarjeta__boton" aria-label="{l s='Cambiar por %producto%' sprintf=['%producto%' => $kcsNombre] d='Modules.Kpycrosssell.Shop'}">{l s='Cambiar' d='Modules.Kpycrosssell.Shop'}</button>
  </div>
</form>
