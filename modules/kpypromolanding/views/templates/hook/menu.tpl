{* El botón «Ofertas y promociones» del menú mientras hay una campaña viva: su nombre y sus colores
   (hook displayKpyPromoMenu, que llama la plantilla parcheada de kpymenu; `movil`, la entrada del
   menú móvil). El mismo HTML que el botón de siempre (las clases de kpymenu) más la clase
   `kpl-menu-campana` y los dos colores en variables, en línea. *}
<style>{literal}
.kpy-menu__element.kpl-menu-campana{align-self:center;height:38px;border-radius:999px;background:var(--kpl-mc-bg);padding-inline:1.1rem;margin-left:.25rem;box-shadow:0 4px 12px rgba(0,0,0,.14)}
.kpy-menu__element.kpl-menu-campana .top-link{color:var(--kpl-mc-fg) !important;font-weight:800;letter-spacing:.01em;white-space:nowrap}
.kpy-menu__element.kpl-menu-campana .kpy-menu__icon{fill:var(--kpl-mc-fg)}
.kpy-menu__element.kpl-menu-campana:hover{background:var(--kpl-mc-fg)}
.kpy-menu__element.kpl-menu-campana:hover .top-link{color:var(--kpl-mc-bg) !important}
.kpy-menu__element.kpl-menu-campana:hover .kpy-menu__icon{fill:var(--kpl-mc-bg)}
.kpy-menu-mobile__element.kpl-menu-campana{background:var(--kpl-mc-bg);color:var(--kpl-mc-fg);border-radius:12px;padding:.6rem .75rem;margin-top:.5rem;font-weight:800;border-bottom:0}
.kpy-menu-mobile__element.kpl-menu-campana .kpy-menu-mobile__element-icon{fill:var(--kpl-mc-fg)}
.kpy-menu-mobile__element.kpl-menu-campana .material-icons{color:var(--kpl-mc-fg)}
{/literal}</style>
{if $kpl_menu.movil}
<a href="{$kpl_menu.url}" class="kpy-menu-mobile__element offers kpl-menu-campana" style="--kpl-mc-bg:{$kpl_menu.fondo};--kpl-mc-fg:{$kpl_menu.color}">
  {include file="components/svg-offer.tpl" classes="kpy-menu-mobile__element-icon"}
  <span>{$kpl_menu.nombre}</span>
  <span class="ms-auto"><i class="material-icons">chevron_right</i></span>
</a>
{else}
<li class="kpy-menu__element kpl-menu-campana" style="--kpl-mc-bg:{$kpl_menu.fondo};--kpl-mc-fg:{$kpl_menu.color}">
  <a href="{$kpl_menu.url}" class="top-link">
    {include file="components/svg-offer.tpl" classes="kpy-menu__icon"}
    <span>{$kpl_menu.nombre}</span>
  </a>
</li>
{/if}
