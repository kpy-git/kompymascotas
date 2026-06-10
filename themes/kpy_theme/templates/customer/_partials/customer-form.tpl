{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{block name='customer_form'}
    {block name='customer_form_errors'}
        {include file='_partials/form-errors.tpl' errors=$errors['']}
    {/block}

  <form action="{block name='customer_form_actionurl'}{$action}{/block}" id="customer-form" class="form-validation js-customer-form" method="post" novalidate>
    <section>
        {block "form_fields"}
            {foreach from=$formFields item="field"}
                {block "form_field"}
                    {if $field.type === "password"}
                        {if isset($mode) && $mode === 'register'}
                            <div class="field-password-policy">
                                {form_field field=$field}
                            </div>
                        {else}
                            <div class="field-password-policy {if $errors.new_password|count == 0 and $errors.password|count == 0 }hidden{/if}">
                                {form_field field=$field}
                            </div>
                            {if $field.name === "new_password" and $errors.new_password|count == 0 and $errors.password|count == 0 }
                                <div class="kpy-form-group">
                                    <span class="btn btn-outline-primary btn-show-password-fields">{l s='Change password' d='Shop.Theme.Actions'}</span>
                                </div>
                            {/if}
                        {/if}

                    {else}
                        {form_field field=$field}
                    {/if}
                {/block}
            {/foreach}
            {$hook_create_account_form nofilter}
        {/block}
    </section>

      {block name='customer_form_footer'}
        <footer class="form-footer">
          <input type="hidden" name="submitCreate" value="1">
            {block "form_buttons"}
              <button class="btn btn-primary form-control-submit" data-link-action="save-customer" type="submit">
                  {if isset($mode) && $mode === "register"}
                      {l s='Create account' d='Shop.Theme.Actions'}
                  {else}
                      {l s='Save' d='Shop.Theme.Actions'}
                  {/if}
              </button>
            {/block}
        </footer>
      {/block}
  </form>
{/block}
