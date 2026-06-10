{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

{$component = "order-products"}

{block name='order_products_table'}
    <div class="{$component}__items">
        {foreach from=$order.products item=product}
          <div class="item">
            <div class="item__image">
                {if $product.cover}
                    <picture>
                        {if isset($product.cover.bySize.default_xs.sources.avif)}
                            <source
                                    srcset="
                      {$product.cover.bySize.default_xs.sources.avif},
                      {$product.cover.bySize.default_md.sources.avif} 2x",
                                    type="image/avif"
                            >
                        {/if}

                        {if isset($product.cover.bySize.default_xs.sources.webp)}
                            <source
                                    srcset="
                      {$product.cover.bySize.default_xs.sources.webp},
                      {$product.cover.bySize.default_md.sources.webp} 2x"
                                    type="image/webp"
                            >
                        {/if}

                        <img
                                class="order-product__img img-fluid"
                                srcset="
                    {$product.cover.bySize.default_xs.url},
                    {$product.cover.bySize.default_md.url} 2x"
                                width="{$product.cover.bySize.default_xs.width}"
                                height="{$product.cover.bySize.default_xs.height}"
                                loading="lazy"
                                alt="{$product.cover.legend}"
                                title="{$product.cover.legend}"
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
                                class="order-product__img img-fluid"
                                srcset="
                    {$urls.no_picture_image.bySize.default_xs.url},
                    {$urls.no_picture_image.bySize.default_md.url} 2x"
                                width="{$urls.no_picture_image.bySize.default_xs.width}"
                                height="{$urls.no_picture_image.bySize.default_xs.height}"
                                loading="lazy"
                        >
                    </picture>
                {/if}
            </div>

            <div class="item__details">
              <p class="order__item__name fw-bold mb-0">
                <a href="{$link->getProductLink($product.id_product)}">
                    {$product.name}
                </a>
              </p>

                {if isset($product.download_link)}
                  <p class="order__item__download my-2">
                    <a href="{$product.download_link}">
                      <i class="material-icons" aria-hidden="true">download</i> {l s='Download' d='Shop.Theme.Actions'}
                    </a>
                  </p>
                {/if}

                {if $product.customizations}
                    {foreach from=$product.customizations item="customization"}
                      <div class="customization">
                        <a href="#" data-bs-toggle="modal"
                           data-bs-target="#product-customizations-modal-{$customization.id_customization}">{l s='Product customization' d='Shop.Theme.Catalog'}</a>
                      </div>

                      <div id="_desktop_product_customization_modal_wrapper_{$customization.id_customization}">
                          {include file='catalog/_partials/customization-modal.tpl' customization=$customization}
                      </div>
                    {/foreach}
                {/if}
            </div>

            <div class="item__price">
              <div class="item-price small">{l s='Price:' d='Shop.Theme.Actions'} <strong>{$product.price}</strong></div>
              <div class="item-quantity small">{l s='Quantity:' d='Shop.Theme.Actions'} <strong>{$product.quantity}</strong></div>
              <div class="item-total">{$product.total}</div>
            </div>

          </div>
        {/foreach}


      <hr>

      <div class="{$component}__subtotals">
      {foreach $order.subtotals as $line}
          {if $line.value}
            <div class="{$component}__subtotal-line">
              <div class="subtotal-label"> {$line.label}</div>
              <div class="subtotal-value">{$line.value}</div>
            </div>
          {/if}
      {/foreach}
      </div>

      <div class="{$component}__totals fw-bold fs-5">
        <div class="{$component}__totals-line">
          <div class="total-label">{$order.totals.total.label}</div>
          <div class="total-value">{$order.totals.total.value}</div>
        </div>
      </div>
    </div>
{/block}
