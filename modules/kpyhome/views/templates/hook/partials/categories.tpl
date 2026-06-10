<section class="popular-categories-section">
  <div class="container">
    <h2 class="section-title">{l s='Find what your pet needs' d='Modules.Kpyhome.Shop'}</h2>
    <p class="section-subtitle">{l s='The most searched categories by owners like you' d='Modules.Kpyhome.Shop'}</p>

    <div class="categories-grid">
      {foreach from=$categories item=category}
        <a href="{url entity=category id=$category.id}" class="category-card" aria-label="Ir a {$category.title}">
          <div class="category-icon-wrapper">
            <div class="category-icon" >
              <img src="{$category.image}" alt="{$category.title}">
            </div>
          </div>
          <h3 class="category-title">{$category.title}</h3>
          <p class="category-description">{$category.subtitle}</p>
          <div class="category-cta">
            {l s='View products' d='Modules.Kpyhome.Shop'}
            <span class="material-icons">chevron_right</span>
          </div>
        </a>
      {/foreach}

      <a href="#" class="category-card" aria-label="Ir a Mejores valorados">
        <div class="category-icon-wrapper">
          <div class="category-icon">
            <img src="{$module_img}categories/8.png">
          </div>
        </div>
        <h3 class="category-title">{l s='Top rated' d='Modules.Kpyhome.Shop'}</h3>
        <p class="category-description">{l s='Most recommended by customers' d='Modules.Kpyhome.Shop'}</p>
        <div class="category-cta">
          {l s='View products' d='Modules.Kpyhome.Shop'}
          <span class="material-icons">chevron_right</span>
        </div>
      </a>

    </div>
  </div>
</section>