{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{if $cart.vouchers.allowed}
    {block name='cart_voucher'}
        <div class="cart-voucher js-cart-voucher">
            {if $cart.vouchers.added}
                <hr />

                {block name='cart_voucher_list'}
                    <ul class="cart-voucher__list">
                        {foreach from=$cart.vouchers.added item=voucher}
                            <li class="cart-voucher__item row">
                                <span class="cart-voucher__name col">{$voucher.name}</span>
                                <div class="cart-voucher__amount d-flex align-items-center justify-content-end col">
                                    <span class="fw-bold">{$voucher.reduction_formatted}</span>
                                    {if isset($voucher.code) && $voucher.code !== ''}
                                        <a href="{$voucher.delete_url}" class="ms-2" data-link-action="remove-voucher"><i class="material-icons" title="{l s='Remove Voucher' d='Shop.Theme.Checkout'}">&#xE872;</i></a>
                                    {/if}
                                </div>
                            </li>
                        {/foreach}
                    </ul>
                {/block}
            {/if}


            <div class="cart-voucher-form">
                <p>
                    {l s='Promo code' d='Shop.Theme.Checkout'}
                </p>

                <div id="promo-code" class="cart-voucher__accordion-collapse js-voucher-accordion">
                    <div class="accordion-body">
                        {block name='cart_voucher_form'}
                            <form class="cart-voucher__form" action="{$urls.pages.cart}" data-link-action="add-voucher" data-ps-ref="voucher-form" method="post">
                                <input type="hidden" name="token" value="{$static_token}">
                                <input type="hidden" name="addDiscount" value="1">
                                <input class="form-control js-voucher-input" type="text" name="discount_name" placeholder="{l s='Paste your voucher here' d='Shop.Theme.Checkout'}" required>
                                <button type="submit" class="btn btn-primary" aria-label="{l s='Apply voucher' d='Shop.Theme.Actions'}">{l s='Apply' d='Shop.Theme.Actions'}</button>
                            </form>
                        {/block}

                        {block name='cart_voucher_notifications'}
                            <div class="cart-voucher__error alert alert-danger js-error" role="alert" tabindex="-1" style="display: none;" data-ps-ref="voucher-error">
                                <i class="cart-voucher__error-icon material-icons" aria-hidden="true">&#xE001;</i>
                                <span class="js-error-text"></span>
                            </div>
                        {/block}
                    </div>
                </div>
            </div>

            {if $cart.discounts|count> 0}
                <p class="mt-4">
                    {l s='Take advantage of our exclusive offers:' d='Shop.Theme.Actions'}
                </p>

                <ul class="js-discount cart-voucher__offers js-discount">
                    {foreach from=$cart.discounts item=discount}
                        <li class="cart-voucher__code">
                            <div class="icon">
                                <img src="{$urls.img_url}voucher-discount.svg" alt="voucher discount">
                            </div>
                            <span class="cart-voucher__code-value js-voucher-code">{$discount.code}</span>
                            <span class="voucher-name"">{$discount.name}</span>
                        </li>
                    {/foreach}
                </ul>
            {/if}
        </div>
    {/block}
{/if}
