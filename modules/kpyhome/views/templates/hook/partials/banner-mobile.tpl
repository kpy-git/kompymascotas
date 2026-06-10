<div class="banner-slider-mobile">
  <div class="slider-container">
    <div class="slider-wrapper">
      {foreach from=$slider_banners item=banner}
        <div class="slider-slide active">
          <a href="{$banner.url}">
            <img src="{$banner.image}" alt="{$banner.description}">
          </a>
        </div>
      {/foreach}
    </div>

    <!-- Controles del slider -->
    <div class="slider-controls">
      <button class="slider-dot active" onclick="currentSlide(1)"></button>
      {for $i=2 to $slider_banners|count}
        <button class="slider-dot" onclick="currentSlide({$i})"></button>
      {/for}
    </div>
  </div>

</div>