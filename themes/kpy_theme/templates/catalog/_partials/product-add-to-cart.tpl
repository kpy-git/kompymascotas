{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
<div class="product__add-to-cart product-add-to-cart js-product-add-to-cart">
  {if !$configuration.is_catalog}

      {block name='product_delivery_times'}
        {if $product.is_virtual	== 0}
          {if $product.additional_delivery_times == 1}
            {if $product.delivery_information}
              <span class="product__delivery__information">{$product.delivery_information}</span>
            {/if}
          {elseif $product.additional_delivery_times == 2}
            {if $product.quantity> 0}
              <span class="product__delivery__information">{$product.delivery_in_stock}</span>
            {* Out of stock message should not be displayed if customer can't order the product. *}
            {elseif $product.quantity <= 0 && $product.add_to_cart_url}
              <span class="product__delivery__information">{$product.delivery_out_stock}</span>
            {/if}
          {/if}
        {/if}
      {/block}

    {block name='product_quantity'}
      <div class="product-actions__wrapper-add-to-cart">
        <div class="product-actions__quantity quantity-button js-quantity-button">
          {include file='components/qty-input.tpl'
            attributes=[
              "id" => "quantity_wanted",
              "class" => "form-control js-quantity-wanted",
              "value" => "{$product.minimal_quantity}",
              "min" => "{$product.minimal_quantity}"
            ]

            marginHelper="mb-2"
          }
        </div>

        <div class="product-actions__button add mt-2">
          <button
            class="btn btn-primary btn-with-icon add-to-cart w-100"
            data-button-action="add-to-cart"
            data-text-alt="{l s='Add' d='Modules.Kpyproductblocks.Shop'}"
            data-text-complete="{l s='Add to cart' d='Shop.Theme.Actions'}"
            type="submit"
            {if !$product.add_to_cart_url}
              disabled
            {/if}
         >
            <i class="material-icons me-1" aria-hidden="true">&#xE547;</i>
            <span>{l s='Add to cart' d='Shop.Theme.Actions'}</span>
          </button>
        </div>

        {hook h='displayProductActions' product=$product}
      </div>
    {/block}



    {*{block name='product_minimal_quantity'}
      <p class="product__minimal-quantity product-minimal-quantity js-product-minimal-quantity d-flex align-items-center mt-3 mt-md-0">
        {if $product.minimal_quantity> 1}
          <i class="material-icons me-2" aria-hidden="true">&#xE88F;</i>
          {l
            s='The minimum purchase order quantity for the product is %quantity%.'
            d='Shop.Theme.Checkout'
            sprintf=['%quantity%' => $product.minimal_quantity]
          }
        {/if}
      </p>
    {/block}*}
  {/if}
</div>
