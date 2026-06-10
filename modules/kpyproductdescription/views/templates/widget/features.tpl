
<h2>{l s='Features' d='Modules.Kpyproductdescription.Shop'} <span class="name-subtitle">{$product.name}</span></h2>

<div class="product-feature">
  <div class="product-feature__title">{l s='Reference' d='Modules.Kpyproductdescription.Shop'}</div>
  <div class="product-feature__value">{$product.reference_to_display}</div>
</div>

<div class="product-feature">
  <div class="product-feature__title">{l s='Brand' d='Modules.Kpyproductdescription.Shop'}</div>
  <div class="product-feature__value">{$product.manufacturer_name}</div>
</div>

{foreach $product.features as $feature}
    <div class="product-feature">
      <div class="product-feature__title">{$feature.name}</div>
      <div class="product-feature__value">{$feature.value}</div>
    </div>
{/foreach}