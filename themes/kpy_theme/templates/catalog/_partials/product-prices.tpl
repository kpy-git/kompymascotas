{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{if $product.show_price}
  <div class="product__prices js-product-prices">
    {block name='product_price'}
      <div class="prices__wrapper d-flex flex-column gap-2 mb-4">
        {if $product.has_discount}
          <div class="product__discount">
          {if $product.kpy_special_price}
            {hook h='displayProductPriceBlock' product=$product type="old_price"}

          {else}
            <span class="product__price-regular">{$product.regular_price}</span>
            {if $product.discount_type === 'percentage'}
              <span class="product__discount-percentage">
                <span class="tag-discount">
                  <img src="{$urls.img_url}tag-dto-start.svg" alt="discount">
                </span>
                {*({l s='Save %percentage%' d='Shop.Theme.Catalog' sprintf=['%percentage%' => $product.discount_percentage_absolute]})*}
                <span class="badge discount">-{$product.discount_percentage_absolute}</span>
              </span>
            {else}
              <span class="product__discount-amount">
                ({l s='Save %amount%' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.discount_to_display]})
              </span>
            {/if}
          {/if}
          </div>
        {/if}
        
        <div>
          <div class="product__current-price{if $product.kpy_special_price} kpy-special-price{/if}">
            {capture name='custom_price'}{hook h='displayProductPriceBlock' product=$product type='custom_price' hook_origin='product_sheet'}{/capture}
            {if '' !== $smarty.capture.custom_price}
              {$smarty.capture.custom_price nofilter}
            {else}
              {$product.price}
            {/if}
          </div>

          {block name='product_unit_price'}
              {widget name='kpyproductblocks' hook='unit_price' product=$product}
          {/block}
        </div>

        {block name='product_pack_price'}
          {if $displayPackPrice}
            <div class="product__pack-price">
              {l s='Instead of %price%' d='Shop.Theme.Catalog' sprintf=['%price%' => $noPackPrice]}
            </div>
          {/if}
        {/block}

        <div class="product__tax-info d-flex align-items-start gap-2">
          <div class="product__tax-label">
            {if !$configuration.taxes_enabled}
              {l s='No tax' d='Shop.Theme.Catalog'}
            {elseif $configuration.display_taxes_label}
              {$product.labels.tax_long}
            {/if}
            
            {hook h='displayProductPriceBlock' product=$product type="price"}
            {hook h='displayProductPriceBlock' product=$product type="after_price"}
            
            
          </div>

          {* Separator *}
          {if $configuration.display_taxes_label && $product.ecotax.amount > 0}<span class="product__sep-price"> - </span>{/if}

          {block name='product_ecotax'}
            {if $product.ecotax.amount> 0}
              <div class="product__ecotax-price">
                {l s='Including %amount% for ecotax' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.ecotax.value]}
                {if $product.has_discount}
                  {l s='(not impacted by the discount)' d='Shop.Theme.Catalog'}
                {/if}
              </div>
            {/if}
          {/block}
        </div>

        {block name='product_without_taxes'}
          {if $priceDisplay == 2}
            <p class="product__price-taxless">{l s='%price% tax excl.' d='Shop.Theme.Catalog' sprintf=['%price%' => $product.price_tax_exc]}</p>
          {/if}
        {/block}
      </div>

        {block name='product_availability'}
            <div class="product-availability">
                {widget name='kpyproductblocks' hook='product_shipping' product=$product}

                {widget name='kpyproductavailabilitymessages' hook='product' product=$product}
            </div>

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
    {/block}

    {hook h='displayProductPriceBlock' product=$product type="weight" hook_origin='product_sheet'}
  </div>
{/if}

