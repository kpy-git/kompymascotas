{*
 * This file is part of the Gamifications module.
 *
 * @author    Sarunas Jonusas, <jonusas.sarunas@gmail.com>
 * @copyright Copyright (c) permanent, Sarunas Jonusas
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

{if !$modules.kpyaccountverify.account_verified }
    {widget name='kpyaccountverify' message={l s='To invite new friends, you must verify your account.' mod='gamifications'}}

{else}
<div class="loyality">
    <div class="loyality__header">
        <p>{l s='For every friend you invite who makes a purchase, you will both receive a 5% discount coupon for future purchases.' mod='gamifications'}</p>
        <img src="{$module_img}friends.svg" alt="trae a un amigo">
    </div>

    <div class="loyality__link">

        <h4>{l s='Your unique referral link:' mod='gamifications'}</h4>
        <!-- <input title="{l s='Your unique referral link' mod='gamifications'}" class="js-gamifications-referral-url-input form-control" type="text" value="{$referral_url}"> -->
        <div class="js-gamifications-referral-url-input">{$referral_url}</div>

        <button class="btn btn-outline-primary btn-with-icon js-gamifications-referral-url-copy mt-3">
            <i class="material-icons">file_copy</i>
            {l s='Copy link' mod='gamifications'}
        </button>
    </div>

    {*<div class="loyality__information">
        {if $invited_customers_count}
            <h4>{l s='You have already invited %s of your friends!' sprintf=[$invited_customers_count] mod='gamifications'}</h4>
        {else}
            <h4>{l s='You have not invited any of your friends yet' mod='gamifications'}</h4>
        {/if}
    </div>*}

    <div class="loyality__steps">
        <h4>{l s='How works?' mod='gamifications'}</h4>
        <ol>
            <li>{l s='Share your link with all your friends.' mod='gamifications'}</li>
            <li>{l s='Your friend signs up using your link.' mod='gamifications'}</li>
            <li>{l s='Make your first purchase.' mod='gamifications'}</li>
            <li>🎉 <strong>{l s='You will both receive a discount coupon!' mod='gamifications'}</strong> 🎉</li>
        </ol>
    </div>

    <div class="loyality__conditions">
        <h4>{l s='Conditions' mod='gamifications'}</h4>
        <ul>
            <li>{l s='Valid only for new users.' mod='gamifications'}</li>
            <li>{l s="The discount coupon will be generated 14 days after your friend's first order is received." mod='gamifications'}</li>
            <li>{l s='The coupons generated are valid for 6 months from the date of creation.' mod='gamifications'}</li>
        </ul>
    </div>
</div>
{/if}