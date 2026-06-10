<h2>{$title_benefits} <span class="name-subtitle">{$product.name}</span></h2>

<div class="product-benefits-box">
  {foreach $benefits as $benefit}
      <div class="product-benefit">
        <span class="benefit-image"><img src="{$benefit.image}.svg" alt="{$benefit.image}"></span>
        <span class="benefit-name">{$benefit.name}</span>
      </div>
  {/foreach}
</div>
