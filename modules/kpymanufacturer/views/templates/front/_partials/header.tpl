<section class="hero-brand">
    <div class="container">
        <div class="hero-brand__mono">

            <!-- Logo -->
            <div class="hero-brand__logo">
                <img src="{$brand_image}" alt="{$brand_name}">
            </div>

            <!-- Titular -->
            <h1 class="hero-brand__title">{$title nofilter}</h1>

            <!-- Texto ampliado -->
            <p class="hero-brand__desc">{$subtitle nofilter}</p>

            <!-- Pills de confianza rápida -->
            {if $pills|count > 1}
                <ul class="hero-pills">
                    {foreach from=$pills item=pill}
                        <li>✔ {$pill}</li>
                    {/foreach}
                </ul>
            {/if}

            <!-- CTA único -->
            <div class="hero-brand__ctas">
                <a href="#productos" class="btn btn-primary p-3">{l s='View featured products' d='Modules.Kpymanufacturer.Shop'}</a>
            </div>

        </div>
    </div>
</section>