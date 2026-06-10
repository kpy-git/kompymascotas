<h2>{l s='Analytical composition' d='Modules.Kpyproductdescription.Shop'} <span class="name-subtitle">{$product.name}</span></h2>

<div class="product__components-flex">
  {foreach $components as $component}
      <div class="product__component">
        <div class="product__component-name">{$component.name}</div>
        <div class="product__component-value">{$component.value}</div>
      </div>
  {/foreach}
</div>