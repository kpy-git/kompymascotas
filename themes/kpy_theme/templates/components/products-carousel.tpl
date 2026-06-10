<section class="products-carousel-section {if isset($carousel_classes)}{$carousel_classes}{/if}" id="{$carousel_id}" >
  <div class="container-full">
    <div class="carousel-wrapper">
      <!-- Imagen fija izquierda -->
      <div class="carousel-hero-image">
        <img src="{$fixed_img}" class="carousel-hero-image-fixed">
        <h2 class="hero-title">{$title}</h2>
        <p class="hero-description">{$subtitle}</p>
        <a href="{$link}" class="hero-cta">{if isset($link_text)}{$link_text}{else}{l s='View all' d='Shop.Theme.Actions'}{/if} <span><i class="material-icons">chevron_right</i></span></a>
      </div>

      <!-- Carrusel productos -->
      <div class="products-horizontal-scroll" data-carousel="{$carousel_id}">
        <div class="scroll-container">
            {foreach from=$products item="product"}
              <div class="product-card-horizontal">
                  {include file="catalog/_partials/miniatures/product.tpl" product=$product}
              </div>
            {/foreach}
        </div>

        <button class="scroll-btn scroll-btn-prev" type="button" aria-label="Anterior" onclick="scrollProducts('prev','{$carousel_id}')" style="opacity: 0; pointer-events: none;">‹</button>
        <button class="scroll-btn scroll-btn-next" type="button" aria-label="Siguiente" onclick="scrollProducts('next','{$carousel_id}')" style="opacity: 1; pointer-events: auto;">›</button>
      </div>
    </div>
  </div>
</section>