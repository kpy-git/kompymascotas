{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{extends file='customer/page.tpl'}

{$componentName = 'customer-link'}

{block name='page_title'}
    {l s='Hi %name%!' sprintf=['%name%' => $customer.firstname] d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}
    {block name='display_customer_account_top'}
        {hook h='displayCustomerAccountTop'}
    {/block}
    <div class="customer-account-grid">
        <div class="customer-account-grid__resume">
            <a href="{$urls.pages.identity}">
                <div class="customer-account-grid__item">
                    <div class="customer-account-grid__item-icon">
                        {include file="components/svg-account.tpl" classes="customer-account__icon"}
                    </div>
                    <div class="customer-account-grid__item-content">
                        <h2>{l s='Information' d='Shop.Theme.Customeraccount'}</h2>
                        <p class="text-kpy-primary">{l s='You are customer from %date%' sprintf=['%date%' => $customer.date_add|date_format:'%d/%m/%Y'] d='Shop.Theme.Customeraccount'}</p>
                        <div class="customer-info-resume">
                            <div class="customer-info-resume__item">
                                <span class="item-name">Nombre</span>
                                <span class="item-value">{$customer.firstname|truncate:25:'...'}</span>
                            </div>
                            <div class="customer-info-resume__item">
                                <span class="item-name">Apellidos</span>
                                <span class="item-value">{$customer.lastname|truncate:25:'...'}</span>
                            </div>
                            <div class="customer-info-resume__item">
                                <span class="item-name">Email</span>
                                {if $modules.kpyfrontcontrollervariables.is_mobile}
                                    <span class="item-value">{$customer.email|truncate:25:'...'}</span>
                                {else}
                                    <span class="item-value">{$customer.email|truncate:40:'...'}</span>
                                {/if}
                            </div>
                            <div class="customer-info-resume__item">
                                <span class="item-name">Fecha de nacimiento</span>
                                <span class="item-value">{if $customer.birthday}{$customer.birthday|date_format:'%d/%m/%Y'}{else}-{/if}</span>
                            </div>
                        </div>
                    </div>
                    <div class="customer-account-grid__chevron-item">
                        <i class="material-icons">chevron_right</i>
                    </div>
                </div>
            </a>
        </div>

        {assign var='title_addresses' value={l s='Addresses' d='Shop.Theme.Customeraccount'}}

        {if $customer.addresses|count}
            {assign var='subtitle_addresses' value={l s='%addresses% saved addresses.' sprintf=['%addresses%' => $customer.addresses|count] d='Shop.Theme.Customeraccount'}}
        {else}
            {assign var='subtitle_addresses' value={l s='You have no saved addresses.' d='Shop.Theme.Customeraccount'}}
        {/if}

        {include file="components/account-grid-item.tpl"
            link=$urls.pages.addresses
            title=$title_addresses
            subtitle=$subtitle_addresses
            iconType='tpl'
            iconValue='components/svg-addresses.tpl'
        }

        {if !$configuration.is_catalog}

            {assign var='orders_subtitle' value={l s='Order history and details' d='Shop.Theme.Customeraccount'}}
            {assign var='orders_title' value={l s='My orders' d='Shop.Theme.Customeraccount'}}

            {include file="components/account-grid-item.tpl"
                link=$urls.pages.history
                title=$orders_title
                subtitle=$orders_subtitle
                iconType='tpl'
                iconValue='components/svg-orders.tpl'
            }

            {*
              <a class="{$componentName}__link col-md-6 col-lg-4" id="order-slips-link" href="{$urls.pages.order_slip}">
              <span class="link-item">
                <i class="material-icons" aria-hidden="true">&#xE8B0;</i>
                {l s='Credit slips' d='Shop.Theme.Customeraccount'}
              </span>
              </a>
            *}

            {if $configuration.voucher_enabled}
                {assign var='vouchers_title' value={l s='Vouchers' d='Shop.Theme.Customeraccount'}}
                {if $modules.kpyfrontcontrollervariables.customerActiveCartRulesCount > 0}
                    {assign var='vouchers_subtitle' value={l s='You have %s vouchers' sprintf=["%s" => $modules.kpyfrontcontrollervariables.customerActiveCartRulesCount] d='Shop.Theme.Customeraccount'}}
                {else}
                    {assign var='vouchers_subtitle' value={l s='You do not have any vouchers.' d='Shop.Notifications.Warning'}}
                {/if}

                {include file="components/account-grid-item.tpl"
                    link=$urls.pages.discount
                    title=$vouchers_title
                    subtitle=$vouchers_subtitle
                    iconType='tpl'
                    iconValue='components/svg-voucher.tpl'
                }
            {/if}

            {if $configuration.return_enabled}
                <a class="{$componentName}__link" id="returns-link" href="{$urls.pages.order_follow}">
                  <span class="link-item">
                    <i class="material-icons" aria-hidden="true">&#xE860;</i>
                    {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}
                  </span>
                </a>
            {/if}

        {/if}

        {block name='display_customer_account'}
            {hook h='displayCustomerAccount'}
        {/block}
    </div>

    <div class="account-footer">
        <a class="{$componentName}__logout btn btn-primary" href="{$urls.actions.logout}">
            {include file="components/svg-close-session.tpl" classes="customer-account__icon logout"}
            {l s='Sign out' d='Shop.Theme.Actions'}
        </a>
    </div>
{/block}

{block name='account_link'}{/block}
