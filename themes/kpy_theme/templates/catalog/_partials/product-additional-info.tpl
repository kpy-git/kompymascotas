{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
<div class="product__additional-info js-product-additional-info">
  {block name='product_availability'}

    {*<div id="product-availability" class="product-availability js-product-availability">
        {if $product.show_availability && $product.availability_message}

          {** First, we prepare the icons and colors we want to use
          {if $product.availability == 'in_stock'}
            {assign 'availability_icon' 'E5CA'}
            {assign 'availability_color' 'success'}
          {elseif $product.availability == 'available'}
            {assign 'availability_icon' 'E002'}
            {assign 'availability_color' 'warning'}
          {elseif $product.availability == 'last_remaining_items'}
            {assign 'availability_icon' 'E002'}
            {assign 'availability_color' 'warning'}
          {else}
            {assign 'availability_icon' 'E14B'}
            {assign 'availability_color' 'danger'}
          {/if}

          {** And render the availability message with icon
          <div class="alert alert-{$availability_color}" role="alert">
            <div class="d-flex">
              <div class="me-2">
                <i class="material-icons rtl-no-flip">&#x{$availability_icon};</i>
              </div>
              <div>
                <div>{$product.availability_message}</div>
                {if !empty($product.availability_submessage)}
                  <div class="mt-1"><small>{$product.availability_submessage}</small></div>
                {/if}
              </div>
            </div>
          </div>
        {/if}
    </div>*}
  {/block}

  {hook h='displayProductAdditionalInfo' product=$product}

  {widget name='kpyproductblocks' hook='product_shipping' product=$product}

  {widget name='kpyproductblocks' hook='secure_payment'}
</div>
