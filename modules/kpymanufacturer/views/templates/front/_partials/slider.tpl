<section class="promos-section section-gap--sm">
    <div class="container">

        <h2 class="section-title mb-2">{l s='Featured Promotions' d='Modules.Kpymanufacturer.Shop'}</h2>
        <p class="section-sub mb-5">{l s='The best deals from this brand, handpicked just for you.' d='Modules.Kpymanufacturer.Shop'}</p>

        <div class="slider-container" id="promo-slider">
            <div class="slider-track">
                {foreach from=$banners item=banner}
                    <a href="{$banner.url}" class="slide"><img src="{$banners_path}/{$banner.image}" alt="{$banner.description}"></a>
                {/foreach}
            </div>

            {if $banners|count > 1}
                <button class="prev-btn">&#10094;</button>
                <button class="next-btn">&#10095;</button>

                <div class="slider-dots" id="dots-container">
            {/if}
            </div>
        </div>

    </div>
</section>