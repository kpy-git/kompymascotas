<section id="productos" class="productos-section section-gap">
    <div class="container">

        <h2 class="section-title" style="text-align:center;">{l s='Feaured products' d='Modules.Kpymanufacturer.Shop'}</h2>
        <p class="section-sub" style="text-align:center;">{l s='The most highly rated by our customers and recommended by veterinarians' d='Modules.Kpymanufacturer.Shop'} </p>

        <div class="productos-tabs" role="tablist">
            <button class="prod-tab active" onclick="switchProdTab('all', this)">{l s='All products' d='Modules.Kpymanufacturer.Shop'}</button>
            <button class="prod-tab" onclick="switchProdTab('perro', this)">
                <img src="{$urls.img_url}dog-face.svg" alt="dog"> {l s='Dogs' d='Modules.Kpymanufacturer.Shop'}
            </button>
            <button class="prod-tab" onclick="switchProdTab('gato', this)">
                <img src="{$urls.img_url}cat-face.svg" alt="cat"> {l s='Cats' d='Modules.Kpymanufacturer.Shop'}
            </button>
        </div>

        <div class="prod-grid" id="prod-grid">

            {foreach from=$products item=product}
                <div class="product-card" data-pet="{$product.pet}">
                    {include file="catalog/_partials/miniatures/product.tpl" product=$product}
                </div>
            {/foreach}
        </div>

        <div class="my-5 w-100 d-inline-flex justify-content-center">
            <a href="{$category_related_url}" class="btn btn-outline-secondary mx-auto">
                {l s='View all products of %brand%' sprintf=['%brand%' => $brand_name] d='Modules.Kpymanufacturer.Shop'}
                <span class="material-icons">chevron_right</span>
            </a>
        </div>

    </div>
</section>