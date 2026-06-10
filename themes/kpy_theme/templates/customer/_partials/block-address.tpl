{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{block name='address_block_item'}
    <article id="address-{$address.id}" class="address" data-id-address="{$address.id}">

        {*<p class="address__alias h4 card-title">{$address.alias}</p>*}
        <div class="address__content">
            {*{$address.formatted nofilter}*}
            <span class="address__firstname">{$address.firstname} {$address.lastname}</span>
            {$address.address1} {if !empty($address.address2|trim)}{$address.address2}{/if}
            {$address.postcode}, {$address.city},
            {$address.state}, {$address.country}
            <br />
            {l s='Telf:' d='Shop.Theme.Customeraccount'} {if !empty($address.phone_mobile|trim)}{$address.phone_mobile}{else}{$address.phone}{/if}

            {* Display the extra field values added in an address from using hook 'additionalCustomerAddressFields' *}
            {hook h='displayAdditionalCustomerAddressFields' address=$address}
        </div>

        {block name='address_block_item_actions'}
            <div class="address__actions">
                <a href="{url entity=address id=$address.id}" data-link-action="edit-address"
                   class="address__edit"> {l s='Edit' d='Shop.Theme.Actions'}</a>

                <a href="{url entity=address id=$address.id params=['delete' => 1, 'token' => $token]}" data-link-action="delete-address"
                   class="address__delete">{l s='Delete' d='Shop.Theme.Actions'}</a>

            </div>
        {/block}
    </article>
{/block}
