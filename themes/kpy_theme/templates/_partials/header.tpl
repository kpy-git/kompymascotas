{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{$headerTopName = 'header-top'}
{$headerBottom = 'header-bottom'}
{$headerBanner = 'header-banner'}
{$headerNavFullWidth = 'header-nav-full-width'}

{capture name="header_banner"}{hook h='displayBanner'}{/capture}
{block name='header_banner'}
  {if !empty($smarty.capture.header_banner)}
    <div class="{$headerBanner}">
      {$smarty.capture.header_banner nofilter}
    </div>
  {/if}
{/block}

{if !$modules.kpyfrontcontrollervariables.is_mobile}
<div class="d-flex header-before">
    <div class="flex-grow-1 text-center">Envíos gratis en península a partir de 39.90€</div>
    {hook h='displayNav2'}
</div>
{/if}

{block name='header_nav'}
  <nav class="{$headerTopName}">
    <div class="container-md">
      <div class="{$headerTopName}-desktop d-none d-md-flex row">
        <div class="{$headerTopName}__left col-md-12">


          <div class="logo">
            {if $shop.logo_details}
              {if $page.page_name == 'index'}<h1 class="{$headerBottom}__h1 mb-0">{/if}
                {renderLogo}
              {if $page.page_name == 'index'}</h1>{/if}
            {/if}
          </div>

          {hook h='displayNav1'}

        </div>

      </div>
    </div>
  </nav>
{/block}

{block name='header_bottom'}
<div class="{$headerBottom}">
  <div class="{$headerBottom}__container container-md">
    <div class="{$headerBottom}__row row gx-2 gx-md-4 align-items-stretch">
      <div class="{$headerBottom}__logo d-flex align-items-center col-auto me-auto me-md-0">

        {if $modules.kpyfrontcontrollervariables.is_mobile}
          {widget name='kpymenu' hook='mobile-menu'}

          <div class="logo-mobile"> {renderLogo}</div>
        {/if}

        {hook h='displayTop'}

        {* MOBILE SEARCH BAR *}
        <div class="ps-searchbar--mobile d-md-none d-flex col-auto">
          <div class="header-block d-flex align-items-center">
            <a class="header-block__action-btn" href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="#searchCanvas" aria-controls="searchCanvas" aria-label="{l s='Show search bar' d='Shop.Theme.Global'}">
              <span class="material-icons header-block__icon">&#xE8B6;</span>
            </a>
          </div>

          <div class="ps-searchbar__offcanvas js-search-offcanvas offcanvas offcanvas-top h-auto" tabindex="-1" id="searchCanvas" aria-labelledby="offcanvasTopLabel">
            <div class="offcanvas-header">
              <div id="_mobile_ps_searchbar" class="ps-searchbar__container"></div>
              <button type="button" class="btn btn-link" data-bs-dismiss="offcanvas" aria-label="{l s='Close search' d='Shop.Theme.Global'}">{l s='Cancel' d='Shop.Theme.Global'}</button>
            </div>
          </div>
        </div>

        <div id="_mobile_ps_customersignin" class="d-md-none d-flex col-auto">
          {* JUST PLACEHOLDER FOR RESPONSIVE COMPONENT TO LOAD REAL ONE *}
          <div class="header-block">
            <a href="{$urls.pages.my_account}" class="header-block__action-btn">
              <i class="material-icons header-block__icon" aria-hidden="true">&#xE853;</i>
            </a>
          </div>
          {* JUST PLACEHOLDER FOR RESPONSIVE COMPONENT TO LOAD REAL ONE *}
        </div>

        {if !$configuration.is_catalog}
          <div id="_mobile_ps_shoppingcart" class="d-md-none d-flex col-auto">
            {* JUST PLACEHOLDER FOR RESPONSIVE COMPONENT TO LOAD REAL ONE *}
            <div class="header-block">
              <a href="{$urls.pages.cart}" class="header-block__action-btn">
                <i class="material-icons header-block__icon" aria-hidden="true">&#xE8CC;</i>
                <span class="header-block__badge">{$cart.products_count}</span>
              </a>
            </div>
            {* JUST PLACEHOLDER FOR RESPONSIVE COMPONENT TO LOAD REAL ONE *}
          </div>
        {/if}
      </div>
      </div>
    </div>
  </div>

  {*<div class="header-footer text-center">
    Espacio para mensajes
  </div>*}

  {capture name="nav_full_width"}{hook h='displayNavFullWidth'}{/capture}
  {if !empty($smarty.capture.nav_full_width)}
    <div class="{$headerNavFullWidth}">
      {$smarty.capture.nav_full_width nofilter}
    </div>
  {/if}
{/block}
