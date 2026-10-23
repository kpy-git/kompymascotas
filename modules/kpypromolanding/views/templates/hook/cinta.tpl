{* La campaña, arriba de toda la tienda, solo mientras está viva: los colores que pone en las tarjetas
   y en la ficha (el tema pinta el fondo de cada etiqueta con el color que le llega, pero no el del
   texto: va aquí, por la clase `kpl-camp--{tipo}` que el módulo añade a la etiqueta; el selector
   lleva más peso que el del tema, `.product-flags .badge:not(.discount).kpy-special-price`, que
   también va con !important; y el precio Special Price de las tarjetas), y la cinta, si la tiene. *}
{if $kpl_cinta.etiquetas || $kpl_cinta.precio_fondo}<style>
{foreach $kpl_cinta.etiquetas as $tipo => $e}ul.product-flags li.badge.kpl-camp.kpl-camp--{$tipo}{literal}{{/literal}color:{$e.color} !important{literal}}{/literal}
{/foreach}{if $kpl_cinta.precio_fondo}{literal}.product-miniature .product-miniature__price.kpy-special-price-listing{{/literal}background-color:{$kpl_cinta.precio_fondo} !important;color:{$kpl_cinta.precio_color} !important{literal}}{/literal}{/if}
</style>{/if}
{if $kpl_cinta.con_cinta}<style>{literal}
/* Con la cinta puesta, la banda de siempre de arriba («Envíos gratis…») no sale: una sola banda, no dos.
   El tema (Hummingbird) la pinta con `d-flex`, que lleva !important dentro de la capa `utilities`; entre
   capas, un !important de la primera capa (`vendors`) gana al de las demás: por eso va ahí. */
@layer vendors{.header .header-before{display:none !important}}
.header .header-before{display:none !important}
/* En el móvil la cabecera va fija y el tema reserva su alto (variable --kpy-menu-mobile-height, que
   también mide la barra y el panel del menú): con la cinta dentro, la cabecera mide 34 px más (la
   cinta va en una sola línea), así que el contenido y el panel del menú bajan esos 34 px; si no, la
   cinta tapa el principio de cada página. */
body.is_mobile .wrapper{padding-top:calc(var(--kpy-menu-mobile-height) + 24px)}
body.is_mobile .kpy-menu-mobile{top:calc(var(--kpy-menu-mobile-height) + 34px);height:calc(100dvh - var(--kpy-menu-mobile-height) - 34px)}
body.is_mobile .kpl-cinta{height:34px;line-height:18px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box}
{/literal}</style><a class="kpl-cinta" href="{$kpl_cinta.url}" style="display:block;background:{$kpl_cinta.fondo};color:{$kpl_cinta.color};text-align:center;padding:8px 16px;font-family:'Raleway',sans-serif;font-weight:800;font-size:14px;letter-spacing:.02em;text-decoration:none;line-height:1.3">{$kpl_cinta.texto} →</a>{/if}
