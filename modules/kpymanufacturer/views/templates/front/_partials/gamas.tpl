<section id="gamas" class="tabs-section section-gap">
    <div class="container">
        <h2 class="section-title">{l s='Choose the right product line for your pet' d='Modules.Kpymanufacturer.Shop'}</h2>
        <p class="section-sub">
            {l s="Find the food that best suits your pet's needs." d='Modules.Kpymanufacturer.Shop'}
        </p>

        <!-- Tabs -->
        <div class="tabs-header" role="tablist">
            <button class="tab-btn active" role="tab" aria-selected="true" onclick="switchTab('dog', this)">
                <img src="{$urls.img_url}dog-face.svg" alt="dog"> {l s='For dogs' d='Modules.Kpymanufacturer.Shop'}
            </button>
            <button class="tab-btn" role="tab" aria-selected="false" onclick="switchTab('cat', this)">
                <img src="{$urls.img_url}cat-face.svg" alt="cat"> {l s='For cats' d='Modules.Kpymanufacturer.Shop'}
            </button>
        </div>

        <!-- PERROS -->
        {if $categories.dog|count > 0}
            <div id="tab-dog" class="tab-content active">
                <div class="gama-grid">
                    {foreach from=$categories.dog item=category}
                        <a href="{$category.url}" class="gama-card">
                            <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                            <h3 class="gama-card__title">{$category.title}</h3>
                            <p class="gama-card__desc">{$category.subtitle}</p>
                            <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                        </a>
                    {/foreach}
                </div>
            </div>
        {/if}

        {if $categories.cat|count > 0}
            <!-- GATOS -->
            <div id="tab-cat" class="tab-content">
                <div class="gama-grid">
                    {foreach from=$categories.cat item=category}
                        <a href="{$category.url}" class="gama-card">
                            <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                            <h3 class="gama-card__title">{$category.title}</h3>
                            <p class="gama-card__desc">{$category.subtitle}</p>
                            <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                        </a>
                    {/foreach}
                </div>
            </div>
        {/if}

    </div>
</section>