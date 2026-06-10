{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

<div id="_desktop_ps_customersignin">
  <div class="ps-customersignin">
    {if $customer.is_logged}
      <div class="dropdown header-block">
        <button
                class="dropdown-toggle header-block__action-btn border-0 bg-transparent"
                id="userMenuButton"
                data-bs-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                aria-label="{l s='View my account (%customerName%)' sprintf=['%customerName%' => $customerName] d='Shop.Theme.Customeraccount'}"
        >
          <div class="nav-module">
            <div class="nav-icon">
              <i class="material-icons header-block__icon" aria-hidden="true">&#xE7FD;</i>
            </div>

            <div class="nav-icon-text">
              {$customer.firstname|truncate:15:'...'}
            </div>
          </div>
        </button>

        <div class="dropdown-menu dropdown-menu-start" aria-labelledby="userMenuButton">
          <span class="dropdown-item">
            {$customer.email|truncate:25:'...'}
          </span>

          <div class="dropdown-divider"></div>

          <a
                  href="{$urls.pages.my_account}"
                  class="dropdown-item"
                  rel="nofollow"
                  {if $urls.current_url == $urls.pages.my_account}aria-current="page"{/if}
          >
            {include file="components/svg-account.tpl" classes="account-dropdown-menu__icon"}
            {l s='Information' d='Shop.Theme.Customeraccount'}
          </a>

          {if $customer.addresses|count}
            <a
                    href="{$urls.pages.addresses}"
                    class="dropdown-item"
                    rel="nofollow"
                    {if $urls.current_url == $urls.pages.addresses}aria-current="page"{/if}
            >
              {include file="components/svg-addresses.tpl" classes="account-dropdown-menu__icon"}
              {l s='Addresses' d='Shop.Theme.Customeraccount'}
            </a>
          {/if}

          {if !$configuration.is_catalog}
            <a
                    href="{$urls.pages.history}"
                    class="dropdown-item"
                    rel="nofollow"
                    {if $urls.current_url == $urls.pages.history}aria-current="page"{/if}
            >
              {include file="components/svg-orders.tpl" classes="account-dropdown-menu__icon"}
              {l s='My orders' d='Shop.Theme.Customeraccount'}
            </a>
          {/if}

          {*{if !$configuration.is_catalog}
            <a
                    href="{$urls.pages.order_slip}"
                    class="dropdown-item"
                    rel="nofollow"
                    {if $urls.current_url == $urls.pages.order_slip}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE8B0;</i>
              {l s='Credit slips' d='Shop.Theme.Customeraccount'}
            </a>
          {/if}*}

          {*{if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a
                    href="{$urls.pages.discount}"
                    class="dropdown-item"
                    rel="nofollow"
                    {if $urls.current_url == $urls.pages.discount}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE54E;</i>
              {l s='Vouchers' d='Shop.Theme.Customeraccount'}
            </a>
          {/if}*}

          <a href="{url entity='module' name='kpypets' controller='display'}" title="{l s='My pets' d='Modules.Kpypets.Shop'}" class="dropdown-item" rel="nofollow">
            {include file="components/svg-pethouse.tpl" classes="account-dropdown-menu__icon"}
            {l s='My pets' d='Modules.Kpypets.Shop'}
          </a>

          {if $configuration.return_enabled && !$configuration.is_catalog}
            <a
                    href="{$urls.pages.order_follow}"
                    class="dropdown-item"
                    rel="nofollow"
                    {if $urls.current_url == $urls.pages.order_follow}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE860;</i>
              {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}
            </a>
          {/if}

          <div class="dropdown-divider"></div>

          <a
                  href="{$logout_url}"
                  class="dropdown-item account-menu--signout"
                  rel="nofollow"
          >
            {include file="components/svg-close-session.tpl" classes="account-dropdown-menu__icon logout"}
            {l s='Sign out' d='Shop.Theme.Actions'}
          </a>
        </div>
      </div>
    {else}
      <div class="header-block">
        <a
                href="{$urls.pages.authentication}?back={$urls.current_url|urlencode}"
                class="header-block__action-btn"
                rel="nofollow"
                aria-label="{l s='Sign in' d='Shop.Theme.Actions'}"
        >
          <div class="nav-module">
            <div class="nav-icon">
              <i class="material-icons header-block__icon" aria-hidden="true">&#xE7FD;</i>
            </div>

            <div class="nav-icon-text">
              {l s='Your account' d='Shop.Theme.Customeraccount'}
            </div>
          </div>
        </a>
      </div>
    {/if}
  </div>
</div>
