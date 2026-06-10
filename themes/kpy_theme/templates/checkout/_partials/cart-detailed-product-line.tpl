{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

<div class="product-line">
    <div class="product-line__image">
        <a class="product-line__title product-line__item" href="{$product.url}"
           data-id_customization="{$product.id_customization|intval}">
            {if $product.default_image}
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
                            class="product-line__img img-fluid"
                            srcset="
              {$product.default_image.bySize.default_xs.url},
              {$product.default_image.bySize.default_md.url} 2x"
                            width="{$product.default_image.bySize.default_xs.width}"
                            height="{$product.default_image.bySize.default_xs.height}"
                            loading="lazy"
                            alt="{$product.name|escape:'quotes'}"
                            title="{$product.name|escape:'quotes'}"
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
                            class="product-line__img img-fluid"
                            srcset="
              {$urls.no_picture_image.bySize.default_xs.url},
              {$urls.no_picture_image.bySize.default_md.url} 2x"
                            width="{$urls.no_picture_image.bySize.default_xs.width}"
                            height="{$urls.no_picture_image.bySize.default_xs.height}"
                            loading="lazy"
                    >
                </picture>
            {/if}
        </a>
    </div>

    <div class="product-line__content">
        <div class="product-line__content-left">
            <a class="product-line__title" href="{if $product.kpy_is_gift}#{else}{$product.url}{/if}}"
               data-id_customization="{$product.id_customization|intval}">
                {$product.name}
            </a>

            {if not $product.kpy_is_gift}
                {if is_array($product.customizations) && $product.customizations|count}
                    {include file='catalog/_partials/product-customization-modal.tpl' product=$product}
                {/if}

                {foreach from=$product.attributes key="attribute" item="value"}
                    <div class="product-line__item product-line__item--info {$attribute|lower}">
                        <span class="product-line__item-label">{$attribute}:</span>
                        <span class="product-line__item-value">{$value}</span>
                    </div>
                {/foreach}

                {if !empty($product.delivery_information)}
                    <div class="product-line__item product-line__item--small-info">
                        {$product.delivery_information}
                    </div>
                {/if}

                {hook h='displayCartExtraProductInfo' product=$product}

                <div class="product-line__item product-line__item--prices mt-1">
                    <span class="product-line__item-price">{$product.total}</span>
                    {if $product.unit_price_full}
                        <span class="product-line__item-unit-price">{$product.unit_price_full}</span>
                    {/if}

                    <span class="product-line__current">
                        <span class="price">{l s='Unit price' d='Shop.Theme.Checkout'} {$product.price}</span>
                        {if $product.unit_price_full}
                            <div class="unit-price-cart">
                                {$product.unit_price_full}
                            </div>
                        {/if}
                    </span>

                    {if $product.has_discount}
                        <span class="product-line__item-regular-price">{$product.regular_price}</span>

                        <span class="product__discount-percentage">
                        <span class="tag-discount"><img src="{$urls.img_url}tag-dto-start.svg" alt="discount"></span>

                        {if $product.discount_type === 'percentage'}
                            <span class="product-line__item-discount product-line__item-discount--percentage badge discount">
                              -{$product.discount_percentage_absolute}
                            </span>
                        {else}
                            <span class="product-line__item-discount product-line__item-discount--amount badge discount">
                              -{$product.discount_to_display}
                            </span>
                        {/if}
                        </span>
                    {/if}

                    {capture name='product_price_block'}{hook h='displayProductPriceBlock' product=$product type="unit_price"}{/capture}
                    {if $smarty.capture.product_price_block}
                        <div class="product-line__item-price-block">
                            {$smarty.capture.product_price_block nofilter}
                        </div>
                    {/if}
                </div>
            {/if}
        </div>

        <div class="product-line__content-right">
            {if empty($product.is_gift) and not $product.kpy_is_gift}
                <a class="js-remove-from-cart"
                   rel="nofollow"
                   href="{$product.remove_from_cart_url}"
                   data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}"
                   data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}"
                   data-id-customization="{$product.id_customization|escape:'javascript'}"
                   data-product-url="{$product.url|escape:'javascript'}"
                   data-product-name="{$product.name|escape:'htmlall':'UTF-8'}"
                   aria-label="{l s='Remove %productName% from cart' sprintf=['%productName%' => $product.name] d='Shop.Theme.Checkout'}"
                >
                    <i class="material-icons">delete</i>
                </a>
            {/if}

            <div class="product-line__quantity-button quantity-button js-quantity-button">

                {if !empty($product.is_gift) or $product.kpy_is_gift}
                  <span class="product-line__gift">
                    <i class="product-line__gift-icon material-icons" aria-hidden="true">&#xE8B1;</i>{$product.quantity} {l s='Gift(s)' d='Shop.Theme.Checkout'}
                  </span>
                {else}
                    {include file='components/qty-input.tpl'
                    attributes=[
                    "class"=>"js-cart-line-product-quantity form-control mw-100",
                    "name"=>"product-quantity-spin",
                    "data-update-url"=>"{$product.update_quantity_url}",
                    "data-product-id"=>"{$product.id_product}",
                    "value"=>"{$product.quantity}",
                    "min"=>"{$product.minimal_quantity}"
                    ]
                    }
                {/if}
            </div>

            {if empty($product.is_gift) and not $product.kpy_is_gift and not $modules.kpyfrontcontrollervariables.is_mobile}
                <div class="product-line__price">{$product.total}</div>
            {/if}
        </div>

        <div class="product-line__actions">


            {block name='hook_cart_extra_product_actions'}
                {hook h='displayCartExtraProductActions' product=$product}
            {/block}
        </div>
    </div>
</div>
