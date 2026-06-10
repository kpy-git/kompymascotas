{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{$componentName = 'order-confirmation'}

<div class="{$componentName}__table{block name='order-confirmation-classes'}{/block}">
  <div class="{$componentName}__items">
      {foreach from=$products item=product name="items_products_confirmation"}
        <div class="item">
          <div class="item__image">
              {if !empty($product.default_image)}
                  <picture>
                      {if isset($product.default_image.bySize.default_xs.sources.avif)}
                          <source
                                  srcset="
                    {$product.default_image.bySize.default_xs.sources.avif},
                    {$product.default_image.bySize.default_md.sources.avif} 2x"
                                  type="image/avif"
                          >
                      {/if}

                      {if isset($product.default_image.bySize.default_xs.sources.webp)}
                          <source
                                  srcset="
                    {$product.default_image.bySize.default_xs.sources.webp},
                    {$product.default_image.bySize.default_md.sources.webp} 2x"
                                  type="image/webp"
                          >
                      {/if}

                      <img
                              class="{$componentName}__product-img img-fluid"
                              srcset="
                  {$product.default_image.bySize.default_xs.url},
                  {$product.default_image.bySize.default_md.url} 2x"
                              src="{$product.default_image.bySize.default_xs.url}"
                              loading="lazy"
                              width="{$product.default_image.bySize.default_xs.width}"
                              height="{$product.default_image.bySize.default_xs.height}"
                              alt="{$product.default_image.legend}"
                              title="{$product.default_image.legend}"
                      >
                  </picture>
              {else}
                  <picture>
                      {if isset($urls.no_picture_image.bySize.default_xs.sources.avif)}
                          <source
                                  srcset="
                    {$urls.no_picture_image.bySize.default_xs.sources.avif},
                    {$urls.no_picture_image.bySize.default_md.sources.avif} 2x"
                                  type="image/avif"
                          >
                      {/if}

                      {if isset($urls.no_picture_image.bySize.default_xs.sources.webp)}
                          <source
                                  srcset="
                    {$urls.no_picture_image.bySize.default_xs.sources.webp},
                    {$urls.no_picture_image.bySize.default_md.sources.webp} 2x"
                                  type="image/webp"
                          >
                      {/if}

                      <img
                              class="{$componentName}__product-img img-fluid"
                              srcset="
                  {$urls.no_picture_image.bySize.default_xs.url},
                  {$urls.no_picture_image.bySize.default_md.url} 2x"
                              src="{$urls.no_picture_image.bySize.default_xs.url}"
                              loading="lazy"
                              width="{$urls.no_picture_image.bySize.default_xs.width}"
                              height="{$urls.no_picture_image.bySize.default_xs.height}"
                      >
                  </picture>
              {/if}
          </div>

          <div class="item__details">
              {if $add_product_link}<a href="{$product.url}" target="_blank">{/if}
              <p class="item__title">{$product.name}</p>
                  {if $add_product_link}</a>{/if}

              {*{if !empty($product.reference)}
                <p class="item__reference">{l s='Reference' d='Shop.Theme.Catalog'} {$product.reference}</p>
              {/if}*}

              {if !isset($product.kpy_is_gift) or not $product.kpy_is_gift}
                {foreach from=$product.attributes key="attribute" item="value"}
                    <div class="item__format {$attribute|lower}">
                    <span class="label small">{$attribute|lower}:</span>
                    <span class="value small">{$value|lower}</span>
                    </div>
                {/foreach}

                {if is_array($product.customizations) && $product.customizations|count}
                  {include file='catalog/_partials/product-customization-modal.tpl' product=$product}
                {/if}

                {hook h='displayProductPriceBlock' product=$product type="unit_price"}

              {else}
                  <span class="product-line__gift">
                    <i class="product-line__gift-icon material-icons" aria-hidden="true">&#xE8B1;</i>{$product.quantity} {l s='Gift(s)' d='Shop.Theme.Checkout'}
                  </span>
              {/if}
          </div>


          <div class="item__price">
              {if !isset($product.kpy_is_gift) or not $product.kpy_is_gift}
                <div class="item-price small">{l s='Unit price:' d='Shop.Theme.Checkout'} <span class="value">{$product.price}</span></div>
                <div class="item-quantity small">{l s='Quantity:' d='Shop.Theme.Checkout'} <span class="value">{$product.quantity}</span></div>
              {/if}
              <div class="item-total">{$product.total}</div>
          </div>

        </div>

          {if !$smarty.foreach.items_products_confirmation.last}
              <hr />
          {/if}
      {/foreach}
  </div>

  <hr>

  <div class="{$componentName}__subtotals">
      {foreach $subtotals as $subtotal}
          {if $subtotal !== null && $subtotal.type !== 'tax' && $subtotal.label !== null}
            <div class="{$componentName}__subtotal-line">
              <div class="subtotal-label">{$subtotal.label}</div>
              <div class="subtotal-value">{if 'discount' == $subtotal.type}-&nbsp;{/if}{$subtotal.value}</div>
            </div>
          {/if}
      {/foreach}
  </div>

  <div class="{$componentName}__totals fw-bold fs-5">
      {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
        <div class="{$componentName}__total-line fw-bold">
          <div class="total-label">{$totals.total.label}&nbsp;{$labels.tax_short}</div>
          <div class="total-value">{$totals.total.value}</div>
        </div>

        <div class="{$componentName}__total-line fw-bold">
          <div class="total-label">{$totals.total_including_tax.label}</div>
          <div class="total-value">{$totals.total_including_tax.value}</div>
        </div>
      {else}
        <div class="{$componentName}__total-line fw-bold">
          <div class="total-label">{$totals.total.label}</div>
          <div class="total-value">{$totals.total.value}</div>
        </div>
      {/if}

      {if $subtotals.tax !== null && $subtotals.tax.label !== null}
        <div class="{$componentName}__total-line">
          <div class="total-label">{l s='%label%:' sprintf=['%label%' => $subtotals.tax.label] d='Shop.Theme.Global'}</div>
          <div class="total-value">{$subtotals.tax.value}</div>
        </div>
      {/if}
  </div>
</div>
