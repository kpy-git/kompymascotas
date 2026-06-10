{$elementName = "kpy-menu-mobile"}

<div class="{$elementName}-hamburger col-auto">
  <div class="btn-hamburger hamburger-off">
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>

<aside class="{$elementName}">
  <nav class="{$elementName}__navigation" aria-hidden="false">
    <h4>{l s='The best for your pet' d='Modules.Kpymenu.Shop'}</h4>
    <hr/>

    <ul class="first-level">
        {foreach $kpymenuWidget.firstLevel as $element}
            {include file="module:kpymenu/views/templates/hook/mobileMenuElement.tpl" elementName=$elementName element=$element}
        {/foreach}

      <a href="{url entity='module' name='kpymanufacturer' controller='brands'}" class="{$elementName}__element brands">
        {include file="components/svg-brands.tpl" classes="{$elementName}__element-icon"}
        <span>{l s='Brands' d='Modules.Kpymenu.Shop'}</span>
        <span class="ms-auto"><i class="material-icons">chevron_right</i></span>
      </a>

      <li class="{$elementName}__element offers">
        {include file="components/svg-offer.tpl" classes="{$elementName}__element-icon"}
        <span>{l s='Offers and promotions' d='Modules.Kpymenu.Shop'}</span>
        <span class="ms-auto"><i class="material-icons">chevron_right</i></span>
      </li>

      {hook h='displayKpyMenuMobileElementsAfter'}
    </ul>
  </nav>

    {foreach $kpymenuWidget.levels as $level}
      <nav class="{$elementName}__navigation" aria-hidden="true">
        <div class="{$elementName}__back">
          <button class="{$elementName}__btn-back" data-parent="{$level.parent.id}"><i class="material-icons">chevron_left</i> {l s='Back' d='Modules.Kpymenu.Shop'}</button>
          <h4 class="{$elementName}__parent-title">{$level.parent.name}</h4>
        </div>

        <ul class="second-level" data-parent="{$level.parent.id}">
          {foreach $level.elements as $element}
            {if not $element.hasChildren}<a href="{$element.link}">{/if}
            {include file="module:kpymenu/views/templates/hook/mobileMenuElement.tpl" elementName=$elementName element=$element}
            {if not $element.hasChildren}</a>{/if}
          {/foreach}
        </ul>
      </nav>
    {/foreach}
</aside>