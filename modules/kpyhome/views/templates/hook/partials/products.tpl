{include file='components/products-carousel.tpl'
  carousel_id='best-products'
  fixed_img="{$module_img}carousel/1.png"
  products=$best_products
  title="Si tú comes mejor… ¿por qué ellos no?"
  subtitle="Alimentación natural con valoraciones que hablan solas."
  link_text="Ver comida natural"
  carousel_classes="pb-4"
  link={url entity='category' id='1008'}
}

{include file='components/products-carousel.tpl'
  carousel_id='alternative-products'
  fixed_img="{$module_img}carousel/4.png"
  products=$alternative_products
  title="Cuida su salud desde el plato."
  subtitle="Alimentación veterinaria para necesidades específicas.."
  link_text="Descubrir"
  link={url entity='category' id='1008'}
}
