{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Your vouchers' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}
    {if !$modules.kpyaccountverify.account_verified }
      {widget name='kpyaccountverify' message={l s='To use coupons, you must verify your account.' d='Shop.Notifications.Warning'}}

    {else}
      {if $cart_rules}
         <div class="account-cart-rules">
           {foreach from=$cart_rules item=$cart_rule}
             {include file="components/voucher.tpl" code=$cart_rule.code name=$cart_rule.name value=$cart_rule.value voucher_date=$cart_rule.voucher_date}
           {/foreach}
         </div>
      {else}
        <div class="alert alert-info" role="alert" data-alert="info">{l s='You do not have any vouchers.' d='Shop.Notifications.Warning'}</div>
      {/if}

    {/if}
{/block}
