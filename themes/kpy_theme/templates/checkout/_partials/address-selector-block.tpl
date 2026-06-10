{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{block name='address_selector_blocks'}
    {foreach $addresses as $address}
      <div class="address-seelctor-item">
        <article id="{$name|classname}-address-{$address.id}" class="address card js-address-item{if $address.id == $selected} selected{/if}" data-id-address="{$address.id}">
          <div class="card-body">
            <label class="form-check-label row">
            <span class="custom-radio col-1">
              <input
                      type="radio"
                      class="form-check-input"
                      name="{$name}"
                      value="{$address.id}"
                      {if $address.id == $selected}checked{/if}
              >
              <i class="form-check-round"></i>
            </span>

              <div class="address__content col-11">
                <p class="address__alias h6 card-title">{$address.alias}</p>
                {*<address class="address__content">{$address.formatted nofilter}</address>*}
                <address class="address__content">
                    {$address.firstname} {$address.lastname}<br />
                    {$address.address1} {if $address.address2}{$address.address2}{/if}<br /> {$address.postcode} {$address.city}<br/>
                    {$address.phone}
                </address>

                  {block name='address_block_item_actions'}
                      {if $interactive}
                        <div class="address__actions text-end">
                          <a
                                  class="address__edit me-2"
                                  data-link-action="edit-address"
                                  href="{url entity='order' params=['id_address' => $address.id, 'editAddress' => $type, 'token' => $token]}"
                          >
                              {l s='Edit' d='Shop.Theme.Actions'}
                          </a>
                          <a
                                  class="address__delete"
                                  data-link-action="delete-address"
                                  href="{url entity='order' params=['id_address' => $address.id, 'deleteAddress' => true, 'token' => $token]}"
                          >
                              {l s='Delete' d='Shop.Theme.Actions'}
                          </a>
                        </div>
                      {/if}
                  {/block}
              </div>
            </label>
          </div>
        </article>
      </div>
    {/foreach}

    {if $interactive}
      <p>
        <button class="ps-hidden-by-js form-control-submit center-block" type="submit">{l s='Save' d='Shop.Theme.Actions'}</button>
      </p>
    {/if}
{/block}
