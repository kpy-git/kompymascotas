<div class="section-header">
  <h2 class="h2-mobile">
    <span class="icon"><img src="{$module_img}fire.svg" alt="fire"></span> Los que nunca fallan</h2>
  <a href="#" class="see-all">{l s='View all' d='Modules.Kpyhome.Shop'}</a>
</div>

<div class="products-scroll">
  {foreach from=$best_products item='product'}
      <div class="card-product">
          {include file="catalog/_partials/miniatures/product.tpl" product=$product}
      </div>
  {/foreach}
</div>

<div class="section-header">
  <h2 class="h2-mobile">
    <span class="icon"><img src="{$module_img}hospital.svg" alt="hospital"></span> Para casos especiales
  </h2>
  <a href="#" class="see-all">{l s='View all' d='Modules.Kpyhome.Shop'}</a>
</div>

<div class="products-scroll">
    {foreach from=$alternative_products item='product'}
      <div class="card-product">
          {include file="catalog/_partials/miniatures/product.tpl" product=$product}
      </div>
    {/foreach}
</div>