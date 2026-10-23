{**
 * Las páginas con listado (una promoción, un tipo, todas): el listado de PrestaShop de siempre con
 * nuestra cabecera. Los ids js-product-list-* son los que usa el tema para ordenar y paginar sin recargar.
 *}
{extends file='catalog/listing/product-list.tpl'}

{block name='content'}
  <div class="kpl kpl--listado">
    {* Con una campaña viva, la cabecera es un bloque con su piel y sus colores; el listado sigue en blanco. *}
    <div id="js-product-list-header" class="kpl-cab{if $kpl.campana} kpl-cab--campana kpl--{$kpl.campana.piel}{/if}"{if $kpl.campana && $kpl.campana.estilo} style="{$kpl.campana.estilo}"{/if}>
      {include file='module:kpypromolanding/views/templates/front/_partials/cabecera.tpl'}
    </div>

    <section id="products">
      {if $listing.products|count}
        {block name='product_list_top'}
          {include file='catalog/_partials/products-top.tpl' listing=$listing}
        {/block}

        {block name='product_list_active_filters'}
          {$listing.rendered_active_filters nofilter}
        {/block}

        {block name='product_list'}
          {include file='catalog/_partials/products.tpl' listing=$listing}
        {/block}

        {block name='product_list_bottom'}
          {include file='catalog/_partials/products-bottom.tpl' listing=$listing}
        {/block}
      {else}
        <div id="js-product-list-top"></div>
        <div id="js-product-list">
          {include file='module:kpypromolanding/views/templates/front/_partials/vacio.tpl'}
        </div>
        <div id="js-product-list-bottom"></div>
      {/if}
    </section>
  </div>
{/block}
