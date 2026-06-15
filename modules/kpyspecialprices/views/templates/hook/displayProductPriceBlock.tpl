<div class="kpy-special-price-block">
    <div class="kpy-special-price-regular-price">
        {l s='Regular price: %price% €' sprintf=['%price%' => $product.kpy_special_price_regular_price] d='Modules.Kpyspecialprices.Shop'}
    </div>
    <div class="kpy-special-price-tag">
        <img src="{$module_img}special-price.svg" alt="special-price-tag">
        {l s='Special price' d='Modules.Kpyspecialprices.Shop'}
    </div>
    {if $product.kpy_special_price_days_left <= 7}
        <div class="kpy-special-price-days-left">
            {if $product.kpy_special_price_days_left == 1}
                {l s='Expire tomorrow' d='Modules.Kpyspecialprices.Shop'}
            {elseif $product.kpy_special_price_days_left == 0}
                {l s='Expire today' d='Modules.Kpyspecialprices.Shop'}
            {else}
                {l s='Expire in %days% days' sprintf=['%days%' => $product.kpy_special_price_days_left] d='Modules.Kpyspecialprices.Shop'}
            {/if}
        </div>
    {/if}
</div>
