{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

{extends file=$layout}

{block name='content'}
  <div class="account-grid">
    <div class="account-grid__menu">
        {include file='components/account-menu.tpl'}
    </div>

    <div class="account-grid__content">
        {block name='page_header_container'}
            {block name='page_title'}
              <div class="page-header">
                <h1 class="h4">{$smarty.block.child}</h1>
              </div>
            {/block}

            {block name='account_link'}
                <div class="account-menu__back-wrapper d-md-none ">
                    <a class="account-menu__back btn btn-outline-primary" href="{$urls.pages.my_account}">
                        <i class="material-icons" aria-hidden="true">&#xE5CB;</i> {l s='Back' d='Shop.Theme.Customeraccount'}
                    </a>
                </div>
            {/block}
        {/block}

        {block name='page_content_container'}
          <section id="content" class="page-content page-customer">
              {block name='page_content_top'}{/block}

              {block name='page_content'}
                <!-- Page content -->
              {/block}
          </section>
        {/block}

        {block name='page_footer_container'}
            {block name='page_footer'}
                {block name='my_account_links'}
                {/block}
            {/block}
        {/block}
    </div>
  </div>
{/block}
