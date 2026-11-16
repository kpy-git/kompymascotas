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

      <a href="{$link->getPageLink('best-sales', true)}" class="category-card" aria-label="Ir a Los más vendidos">
        <div class="category-icon-wrapper">
          <div class="category-icon">
            <img src="{$module_img}categories/8.png" alt="">
          </div>
        </div>
        <h3 class="category-title">{l s='Best sales' d='Modules.Kpyhome.Shop'}</h3>
        <p class="category-description">{l s='What our customers say most often' d='Modules.Kpyhome.Shop'}</p>
        <div class="category-cta">
          {l s='View products' d='Modules.Kpyhome.Shop'}
          <span class="material-icons">chevron_right</span>
        </div>
      </a>

    </div>
  </div>
</section>
