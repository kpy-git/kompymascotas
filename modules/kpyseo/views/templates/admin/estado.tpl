{**
 * La cabecera de la pantalla del módulo: avisos, estado de los sitemaps, IndexNow y los dos botones.
 *}
{foreach $kseo_avisos as $aviso}
  <div class="alert {if $aviso[0] == 'ok'}alert-success{else}alert-danger{/if}">{$aviso[1]|escape:'html':'UTF-8'}</div>
{/foreach}
<div class="panel">
  <h3><i class="icon-globe"></i> Estado</h3>
  <p><strong>Sitemap:</strong> <a href="{$kseo_sitemap_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$kseo_sitemap_url|escape:'html':'UTF-8'}</a></p>
  <p>
    <strong>Última generación:</strong>
    {if $kseo_estado.generado}
      {$kseo_estado.generado|date_format:'%d/%m/%Y %H:%M'} ·
      {$kseo_estado.paginas} páginas, {$kseo_estado.categorias} categorías, {$kseo_estado.marcas} marcas y
      {$kseo_estado.productos} productos en {$kseo_estado.ficheros_productos} fichero(s)
    {else}
      todavía no se ha generado (se genera con la primera visita a /sitemap.xml)
    {/if}
  </p>
  <p>
    <strong>IndexNow:</strong> clave publicada en
    <a href="{$kseo_indexnow_url_clave|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$kseo_indexnow_url_clave|escape:'html':'UTF-8'}</a>
    {if $kseo_indexnow_ultimo} · último envío: {$kseo_indexnow_ultimo|escape:'html':'UTF-8'}{/if}
  </p>
  <form method="post" class="form-inline">
    <button type="submit" name="submitKpyseoSitemap" value="1" class="btn btn-default"><i class="icon-refresh"></i> Regenerar los sitemaps ahora</button>
    <button type="submit" name="submitKpyseoIndexnow" value="1" class="btn btn-default"><i class="icon-send"></i> Enviar todas las URL a IndexNow</button>
  </form>
  <p class="help-block" style="margin-top:12px">
    Para que los buscadores lo encuentren solos, <code>robots.txt</code> debe llevar la línea
    <code>Sitemap: {$kseo_sitemap_url|escape:'html':'UTF-8'}</code> (la añade desarrollo en el fichero), y conviene enviar
    esa misma URL en Search Console y en Bing Webmaster Tools.
  </p>
</div>
