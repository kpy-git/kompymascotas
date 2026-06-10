{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='content_columns'}
  <div class="container">

    <h1 class="h1 text-center">{l s='Our brands' d='Modules.Kpymanufacturer.Shop'}</h1>

    <div class="brands-letters">
        <div class="brand-letter" data-letter="0">{l s='All' d='Modules.Kpymanufacturer.Shop' }</div>
      {foreach from=$letters item="letter"}
          <div class="brand-letter" data-letter="{$letter}">{$letter}</div>
      {/foreach}
    </div>

    <div class="brands">
      {foreach from=$brandsByLetter item="brands" key="letter"}
        <div class="brands-container" data-letter="{$letter}">
          <div class="brand-letter-separator">
            {$letter}
          </div>

          <div class="brands-letter-wrapper">
            {foreach from=$brands item="brand"}
                <a href="{$brand.url}" class="brand">
                  <div class="brand-image"><img src="{$brand.image}" alt="{$brand.name}"></div>
                  <div class="brand-name">{if $modules.kpyfrontcontrollervariables.is_mobile}{$brand.name}{else}{$brand.name|truncate:30:'...'}{/if}</div>
                </a>
            {/foreach}
          </div>
        </div>
      {/foreach}
    </div>

  </div>
{/block}