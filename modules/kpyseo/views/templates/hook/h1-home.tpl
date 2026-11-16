{**
 * El H1 de texto de la portada (el logo deja de serlo). Texto y subtítulo se cambian en el módulo.
 *}
<div class="kseo-portada container-md">
  <h1 class="kseo-portada__h1">{$kseo_h1|escape:'html':'UTF-8'}</h1>
  {if $kseo_sub}<p class="kseo-portada__sub">{$kseo_sub|escape:'html':'UTF-8'}</p>{/if}
</div>
