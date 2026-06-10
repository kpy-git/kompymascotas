<div class="row">
  <div class="product-features col-md-4 col-xs-12">
    {include file="module:kpyproductdescription/views/templates/widget/features.tpl"}
  </div>

  {if $benefits|@count}
    <div class="product__benefits offset-md-1 col-md-7 col-xs-12 mb-xs-2 mt-xs-2 mb-md-0 mt-md-0">
      {include file="module:kpyproductdescription/views/templates/widget/benefits.tpl" benefits=$benefits}
    </div>
  {/if}

</div>

<div class="row mt-4">
    <div class="product__ingredients col-md-4 col-xs-12">
      {include file="module:kpyproductdescription/views/templates/widget/ingredients.tpl" ingredients=$ingredients}
    </div>

    {if $components|@count}
      <div class="product__components col-md-7 offset-md-1 col-xs-12 mt-xs-4 mt-md-0">
        {include file="module:kpyproductdescription/views/templates/widget/components.tpl"}
      </div>
    {/if}
</div>


{if !empty($rations_table)}
    {include file="module:kpyproductdescription/views/templates/widget/daily_rations.tpl"}
{/if}

  {* descripcion original
  <div class="info accordion-item" id="description">
    <h2 class="info__title accordion-header" id="product-description-heading">
      <button class="accordion-button" type="button" data-bs-toggle="collapse"
              data-bs-target="#product-description-collapse" aria-expanded="true"
              aria-controls="product-description-collapse">
          {l s='Description' d='Shop.Theme.Catalog'}
      </button>
    </h2>
    <div id="product-description-collapse" class="info__content accordion-collapse collapse show"
         data-bs-parent="#product-infos-accordion" aria-labelledby="product-description-heading">
      <div class="product__description accordion-body rich-text">
          {$product.description nofilter}
      </div>
    </div>
  </div>
  *}

<div class="product__description mt-xs-2 mt-md-4">
  <h2>{l s='Description' d='Modules.Kpyproductdescription.Shop'} <span class="name-subtitle">{$product.name}</span></h2>

    {$product.description nofilter}

</div>