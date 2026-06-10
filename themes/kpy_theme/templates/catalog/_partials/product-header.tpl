
<div class="product-brand">
    {if isset($modules.kpymanufacturer.manufacturer_rewrite) && !empty($modules.kpymanufacturer.manufacturer_rewrite)}
      <a href="{$modules.kpymanufacturer.manufacturer_rewrite}" item-prop="{$product.manufacturer_name}">{$product.manufacturer_name|lower|capitalize}</a>

    {elseif isset($modules.kpymanufacturer.category_manufacturer) && $modules.kpymanufacturer.category_manufacturer > 0}
      <a href="{url entity='category' id=$modules.kpymanufacturer.category_manufacturer}" item-prop="{$product.manufacturer_name}">{$product.manufacturer_name|lower|capitalize}</a>

    {else}
      <a href="{url entity='manufacturer' id=$product.id_manufacturer}" item-prop="{$product.manufacturer_name}">{$product.manufacturer_name|lower|capitalize}</a>
    {/if}
</div>

  {block name='product_header'}
    <h1 class="h4 product__name">{block name='page_title'}{$product.name}{/block}</h1>
  {/block}

  {widget name='productcomments' hook='additional_info' product=$product}

  {block name='product_description_short'}
    <div class="product__description-short pe-4">
      {if $product.id_manufacturer == 178}
          {$product.description_short nofilter}
      {else}
        {if $shortly}
            <span>{$product.description_short|strip_tags|replace:"&nbsp;":" "|truncate:180:" ..."}</span>
            {if $product.description_short|strip_tags|replace:"&nbsp;":" "|count_characters > 160}
              <br />
              <a href="#product-description-heading" class="product-complete-description">{l s='Show full description' d='Shop.Theme.Catalog'}</a>
            {/if}
        {else}
            {$product.description_short|strip_tags|replace:"&nbsp;":" "}
        {/if}
      {/if}

    </div>
  {/block}
